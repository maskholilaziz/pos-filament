<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\Bundle;
use App\Models\Product;
use App\Models\Category;
use Filament\Pages\Page;
use App\Models\Ingredient;
use Filament\Actions\Action;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class PointOfSale extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static string $view = 'filament.pages.point-of-sale';
    protected static ?string $navigationLabel = 'POS';
    protected static ?int $navigationSort = -1;

    // State untuk modal
    public $selectedItem = null;
    public string $selectedItemType = '';
    public bool $showOptionsModal = false;
    public array $selectedOptions = [];
    public string $notes = '';

    // State untuk filter & keranjang
    public ?int $selectedCategory = null;
    public string $search = '';
    public Collection $cart;
    public float $total = 0.00;
    public float $optionsTotal = 0.00;

    public function mount(): void
    {
        $this->cart = collect();
    }

    // Mengambil data Kategori
    public function getCategoriesProperty()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    // Mengambil data Produk
    public function getProductsProperty()
    {
        $products = Product::with(['ingredients', 'optionGroups'])
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get();

        // Tambahkan properti virtual 'can_be_sold' ke setiap produk
        $products->each(function ($product) {
            $product->can_be_sold = true;
            // Jika produk punya resep, cek stok bahan bakunya
            if ($product->ingredients->isNotEmpty()) {
                foreach ($product->ingredients as $ingredient) {
                    if ($ingredient->stock < $ingredient->pivot->quantity_used) {
                        $product->can_be_sold = false;
                        break;
                    }
                }
            }
        });

        return $products;
    }

    // Mengambil data Paket Promo yang aktif
    public function getBundlesProperty()
    {
        $today = now()->toDateString();
        $bundles = Bundle::with('products.ingredients') // Eager load resep dari komponen
            ->where('is_active', true)
            // ->where(function ($query) use ($today) {
            //     $query->where(function ($subQuery) use ($today) {
            //         $subQuery->where('start_date', '<=', $today)->where('end_date', '>=', $today);
            //     })
            //         ->orWhere(function ($subQuery) {
            //             $subQuery->whereNull('start_date')->whereNull('end_date');
            //         });
            // })
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->selectedCategory, fn($q) => $q->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get();

        $bundles->each(function ($bundle) {
            $bundle->can_be_sold = true;
            foreach ($bundle->products as $product) {
                // Cek resep dari setiap produk di dalam bundle
                foreach ($product->ingredients as $ingredient) {
                    if ($ingredient->stock < $ingredient->pivot->quantity_used * $product->pivot->quantity) {
                        $bundle->can_be_sold = false;
                        return false; // Hentikan loop untuk bundle ini
                    }
                }
            }
        });

        return $bundles;
    }

    public function selectCategory(?int $categoryId = null): void
    {
        $this->selectedCategory = $categoryId;
    }

    // Memilih item (produk atau paket) untuk ditampilkan di modal
    public function selectItem(int $itemId, string $type): void
    {
        if ($type === 'product') {
            $this->selectedItem = Product::with('optionGroups.options')->find($itemId);
            $this->selectedItemType = 'product';
        } elseif ($type === 'bundle') {
            $this->selectedItem = Bundle::find($itemId);
            $this->selectedItemType = 'bundle';
        }

        if (!$this->selectedItem) return;

        $this->showOptionsModal = true;
        $this->reset('selectedOptions', 'notes', 'optionsTotal');
    }

    // Menghitung total harga opsi saat dipilih di modal
    public function updatedSelectedOptions(): void
    {
        $this->optionsTotal = 0;
        if (!$this->selectedItem || $this->selectedItemType !== 'product') return;

        foreach ($this->selectedItem->optionGroups as $group) {
            if (isset($this->selectedOptions[$group->id])) {
                if (is_array($this->selectedOptions[$group->id])) { // Checkbox
                    foreach ($this->selectedOptions[$group->id] as $optionId => $value) {
                        if ($value) {
                            $option = $group->options->find($optionId);
                            if ($option) $this->optionsTotal += $option->price;
                        }
                    }
                } else { // Radio
                    $optionId = $this->selectedOptions[$group->id];
                    $option = $group->options->find($optionId);
                    if ($option) $this->optionsTotal += $option->price;
                }
            }
        }
    }

    // Menambahkan item dari modal ke keranjang
    public function addToCartFromModal(): void
    {
        if (!$this->selectedItem) return;

        $item = $this->selectedItem;
        $type = $this->selectedItemType;
        $notes = trim($this->notes);
        $options = $this->selectedOptions;
        $cartId = md5($type . $item->id . json_encode($options) . $notes);

        // --- Logika baru pengecekan stok total sebelum menambah ---
        $requiredIngredients = collect();

        // 1. Kumpulkan kebutuhan dari keranjang yang sudah ada
        foreach ($this->cart as $cartItem) {
            $quantityInCart = $cartItem['quantity'];
            // Jika item yang akan ditambah sudah ada di keranjang, simulasikan penambahannya
            if ($this->cart->has($cartId) && $cartItem === $this->cart->get($cartId)) {
                $quantityInCart++;
            }

            if ($cartItem['item_type'] === 'product') {
                $product = Product::with('ingredients')->find($cartItem['item_id']);
                foreach ($product->ingredients as $ingredient) {
                    $requiredIngredients[$ingredient->id] = ($requiredIngredients[$ingredient->id] ?? 0) + ($ingredient->pivot->quantity_used * $quantityInCart);
                }
            } elseif ($cartItem['item_type'] === 'bundle') {
                $bundle = Bundle::with('products.ingredients')->find($cartItem['item_id']);
                foreach ($bundle->products as $productComponent) {
                    foreach ($productComponent->ingredients as $ingredient) {
                        $totalUsed = $ingredient->pivot->quantity_used * $productComponent->pivot->quantity * $quantityInCart;
                        $requiredIngredients[$ingredient->id] = ($requiredIngredients[$ingredient->id] ?? 0) + $totalUsed;
                    }
                }
            }
        }

        // 2. Jika item yang akan ditambah belum ada di keranjang, tambahkan kebutuhannya
        if (!$this->cart->has($cartId)) {
            if ($type === 'product') {
                foreach ($item->ingredients as $ingredient) {
                    $requiredIngredients[$ingredient->id] = ($requiredIngredients[$ingredient->id] ?? 0) + $ingredient->pivot->quantity_used;
                }
            } elseif ($type === 'bundle') {
                foreach ($item->products as $productComponent) {
                    foreach ($productComponent->ingredients as $ingredient) {
                        $totalUsed = $ingredient->pivot->quantity_used * $productComponent->pivot->quantity;
                        $requiredIngredients[$ingredient->id] = ($requiredIngredients[$ingredient->id] ?? 0) + $totalUsed;
                    }
                }
            }
        }

        // 3. Cek apakah kebutuhan melebihi stok
        if ($requiredIngredients->isNotEmpty()) {
            $ingredientsInStock = Ingredient::whereIn('id', $requiredIngredients->keys())->pluck('stock', 'id');
            foreach ($requiredIngredients as $id => $needed) {
                if ($ingredientsInStock[$id] < $needed) {
                    Notification::make()->title('Stok bahan baku tidak cukup!')->body("Maksimal pesanan untuk bahan ini telah tercapai di keranjang.")->warning()->send();
                    $this->closeOptionsModal(); // Tutup modal setelah notifikasi
                    return;
                }
            }
        }

        // 4. Jika semua bahan baku tersedia, lanjutkan proses penambahan ke keranjang
        if ($this->cart->has($cartId)) {
            $cartItem = $this->cart->get($cartId);
            $cartItem['quantity']++;
            $this->cart->put($cartId, $cartItem);
        } else {
            $optionsPrice = 0;
            $optionsText = [];
            $optionsRaw = [];

            if ($type === 'product') {
                $optionsRaw = $this->selectedOptions;
                foreach ($optionsRaw as $groupId => $optionValue) {
                    $group = $item->optionGroups->find($groupId);
                    if ($group->type === 'radio') {
                        $option = $group->options->find($optionValue);
                        if ($option) {
                            $optionsText[$group->name] = $option->name;
                            $optionsPrice += $option->price;
                        }
                    } else {
                        $selected = [];
                        foreach ($optionValue as $optionId => $isSelected) {
                            if ($isSelected) {
                                $option = $group->options->find($optionId);
                                if ($option) {
                                    $selected[] = $option->name;
                                    $optionsPrice += $option->price;
                                }
                            }
                        }
                        if (!empty($selected)) $optionsText[$group->name] = implode(', ', $selected);
                    }
                }
            }

            $this->cart->put($cartId, [
                'item_id'       => $item->id,
                'item_type'     => $type,
                'name'          => $item->name,
                'price'         => $type === 'product' ? $item->selling_price : $item->price,
                'options_price' => $optionsPrice,
                'quantity'      => 1,
                'options_text'  => $optionsText,
                'options_raw'   => $optionsRaw,
                'notes'         => $notes,
            ]);
        }
        $this->calculateTotal();
        $this->closeOptionsModal();
    }

    public function closeOptionsModal(): void
    {
        $this->showOptionsModal = false;
        $this->reset('selectedItem', 'selectedItemType', 'selectedOptions', 'notes', 'optionsTotal');
    }

    public function updateQuantity(string $cartId, string $type): void
    {
        if (!$this->cart->has($cartId)) return;

        $item = $this->cart->get($cartId);

        if ($type === 'decrement') {
            if ($item['quantity'] > 1) {
                $item['quantity']--;
                $this->cart->put($cartId, $item);
            } else {
                $this->cart->forget($cartId);
            }
            $this->calculateTotal();
            return;
        }

        // --- Logika baru untuk INCREMENT ---
        if ($type === 'increment') {
            // 1. Kumpulkan semua bahan baku yang dibutuhkan untuk SELURUH keranjang
            $requiredIngredients = collect();

            foreach ($this->cart as $cartItem) {
                $quantityInCart = ($cartItem === $item) ? $cartItem['quantity'] + 1 : $cartItem['quantity'];

                if ($cartItem['item_type'] === 'product') {
                    $product = Product::with('ingredients')->find($cartItem['item_id']);
                    foreach ($product->ingredients as $ingredient) {
                        $requiredIngredients[$ingredient->id] = ($requiredIngredients[$ingredient->id] ?? 0) + ($ingredient->pivot->quantity_used * $quantityInCart);
                    }
                } elseif ($cartItem['item_type'] === 'bundle') {
                    $bundle = Bundle::with('products.ingredients')->find($cartItem['item_id']);
                    foreach ($bundle->products as $productComponent) {
                        foreach ($productComponent->ingredients as $ingredient) {
                            $totalUsed = $ingredient->pivot->quantity_used * $productComponent->pivot->quantity * $quantityInCart;
                            $requiredIngredients[$ingredient->id] = ($requiredIngredients[$ingredient->id] ?? 0) + $totalUsed;
                        }
                    }
                }
            }

            // 2. Cek apakah kebutuhan melebihi stok
            $ingredientsInStock = Ingredient::whereIn('id', $requiredIngredients->keys())->pluck('stock', 'id');
            foreach ($requiredIngredients as $id => $needed) {
                if ($ingredientsInStock[$id] < $needed) {
                    Notification::make()->title('Stok bahan baku tidak cukup!')->body("Maksimal pesanan untuk bahan ini telah tercapai di keranjang.")->warning()->send();
                    return; // Hentikan proses jika satu bahan saja tidak cukup
                }
            }

            // 3. Jika semua bahan baku tersedia, tambahkan jumlahnya
            $item['quantity']++;
            $this->cart->put($cartId, $item);
            $this->calculateTotal();
        }
    }

    public function removeFromCart(string $cartId): void
    {
        $this->cart->forget($cartId);
        $this->calculateTotal();
    }

    protected function calculateTotal(): void
    {
        $this->total = $this->cart->sum(fn($item) => ($item['price'] + $item['options_price']) * $item['quantity']);
    }

    public function clearCart(): void
    {
        $this->cart = collect();
        $this->calculateTotal();
    }

    protected function getActions(): array
    {
        return [
            'process_payment' => Action::make('process_payment')->label('Proses Pembayaran')->color('success')->icon('heroicon-o-currency-dollar')->requiresConfirmation()->modalHeading('Konfirmasi Pembayaran')
                ->form([
                    TextInput::make('customer_name')->label('Nama Pelanggan')->default('Pelanggan'),
                    TextInput::make('amount_paid')->label('Uang Dibayar')->numeric()->required()->prefix('Rp'),
                ])
                ->action(fn(array $data) => $this->processPayment($data))
                ->disabled($this->cart->isEmpty()),
        ];
    }

    public function processPayment(array $data): void
    {
        if ($this->cart->isEmpty() || $data['amount_paid'] < $this->total) {
            Notification::make()->title($this->cart->isEmpty() ? 'Keranjang kosong!' : 'Uang pembayaran tidak cukup!')->danger()->send();
            return;
        }
        try {
            DB::transaction(function () use ($data) {
                $order = Order::create([
                    'invoice_number' => 'INV-' . time(),
                    'user_id'        => Auth::id(),
                    'customer_name'  => $data['customer_name'],
                    'total_price'    => $this->total,
                    'amount_paid'    => $data['amount_paid'],
                    'change'         => $data['amount_paid'] - $this->total,
                ]);

                foreach ($this->cart as $item) {
                    $order->items()->create([
                        'product_id'        => $item['item_type'] === 'product' ? $item['item_id'] : null,
                        'product_name'      => $item['name'],
                        'quantity'          => $item['quantity'],
                        'unit_price'        => $item['price'] + $item['options_price'],
                        'total_price'       => ($item['price'] + $item['options_price']) * $item['quantity'],
                        'selected_options'  => $item['options_raw'],
                        'notes'             => $item['notes'],
                    ]);

                    if ($item['item_type'] === 'product') {
                        $product = Product::with('ingredients')->find($item['item_id']);
                        if ($product) {
                            foreach ($product->ingredients as $ingredient) {
                                $ingredient->decrement('stock', $ingredient->pivot->quantity_used * $item['quantity']);
                            }
                        }
                    } elseif ($item['item_type'] === 'bundle') {
                        $bundle = Bundle::with('products.ingredients')->find($item['item_id']);
                        if ($bundle) {
                            foreach ($bundle->products as $productComponent) {
                                foreach ($productComponent->ingredients as $ingredient) {
                                    $quantityToDecrement = $ingredient->pivot->quantity_used * $productComponent->pivot->quantity * $item['quantity'];
                                    $ingredient->decrement('stock', $quantityToDecrement);
                                }
                            }
                        }
                    }
                }
            });
            $change = $data['amount_paid'] - $this->total;
            Notification::make()->title('Transaksi Berhasil!')->body('Kembalian: ' . number_format($change))->success()->send();
            $this->clearCart();
        } catch (\Exception $e) {
            Notification::make()->title('Transaksi Gagal!')->body($e->getMessage())->danger()->send();
        }
    }
}
