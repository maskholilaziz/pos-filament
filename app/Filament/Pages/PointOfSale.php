<?php

namespace App\Filament\Pages;

use App\Models\Bundle;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        return Product::query()
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get();
    }

    // Mengambil data Paket Promo yang aktif
    public function getBundlesProperty()
    {
        $today = now()->toDateString();

        $bundles = Bundle::query()
            ->with('products') // Eager load relasi produk
            ->where('is_active', true)
            // ->where(function ($query) use ($today) {
            //     $query->where(function ($subQuery) use ($today) {
            //         $subQuery->where('start_date', '<=', $today)
            //             ->where('end_date', '>=', $today);
            //     })
            //         ->orWhere(function ($subQuery) {
            //             $subQuery->whereNull('start_date')
            //                 ->whereNull('end_date');
            //         });
            // })
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->when($this->selectedCategory, fn($q) => $q->whereRaw('1 = 0'))
            ->orderBy('name')
            ->get();

        // Tambahkan properti virtual 'can_be_sold' ke setiap bundle
        $bundles->each(function ($bundle) {
            $bundle->can_be_sold = true;
            foreach ($bundle->products as $product) {
                if ($product->stock < $product->pivot->quantity) {
                    $bundle->can_be_sold = false;
                    break; // Hentikan pengecekan jika satu komponen saja tidak cukup
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

        $cartId = md5($type . $item->id . json_encode($this->selectedOptions) . $notes);

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

        if ($type === 'increment') {
            if ($item['item_type'] === 'product') {
                $product = Product::find($item['item_id']);
                if ($item['quantity'] + 1 > $product->stock) {
                    Notification::make()->title('Jumlah melebihi stok!')->warning()->send();
                    return;
                }
            }
            $item['quantity']++;
            $this->cart->put($cartId, $item);
        } elseif ($type === 'decrement') {
            if ($item['quantity'] > 1) {
                $item['quantity']--;
                $this->cart->put($cartId, $item);
            } else {
                $this->cart->forget($cartId);
            }
        }
        $this->calculateTotal();
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
                        Product::find($item['item_id'])->decrement('stock', $item['quantity']);
                    } elseif ($item['item_type'] === 'bundle') {
                        $bundle = Bundle::with('products')->find($item['item_id']);
                        foreach ($bundle->products as $product) {
                            $product->decrement('stock', $product->pivot->quantity * $item['quantity']);
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
