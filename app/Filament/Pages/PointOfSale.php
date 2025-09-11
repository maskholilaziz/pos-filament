<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PointOfSale extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static string $view = 'filament.pages.point-of-sale';
    protected static ?string $navigationLabel = 'POS';
    protected static ?int $navigationSort = -1;

    public ?Product $selectedProduct = null;
    public bool $showOptionsModal = false;
    public array $selectedOptions = [];
    public string $notes = '';

    public ?int $selectedCategory = null;
    public string $search = '';
    public Collection $cart;
    public float $total = 0.00;
    public float $optionsTotal = 0.00;

    public function mount(): void
    {
        $this->cart = collect();
    }

    public function getCategoriesProperty()
    {
        return Category::where('is_active', true)->orderBy('name')->get();
    }

    public function getProductsProperty()
    {
        return Product::with('optionGroups') // Eager load relasi
            ->where('is_active', true)
            ->whereHas('category', fn($q) => $q->where('is_active', true))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($this->search, fn($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            ->orderBy('name')
            ->get();
    }

    public function selectCategory(?int $categoryId = null): void
    {
        $this->selectedCategory = $categoryId;
    }

    public function selectProduct(int $productId): void
    {
        $product = Product::with('optionGroups.options')->find($productId);
        if (!$product) return;

        $this->selectedProduct = $product;
        $this->showOptionsModal = true;
        $this->reset('selectedOptions', 'notes', 'optionsTotal');
    }

    public function updatedSelectedOptions(): void
    {
        $this->optionsTotal = 0;
        if (!$this->selectedProduct) return;

        foreach ($this->selectedProduct->optionGroups as $group) {
            if (isset($this->selectedOptions[$group->id])) {
                if (is_array($this->selectedOptions[$group->id])) {
                    foreach ($this->selectedOptions[$group->id] as $optionId => $value) {
                        if ($value) {
                            $option = $group->options->find($optionId);
                            if ($option) $this->optionsTotal += $option->price;
                        }
                    }
                } else {
                    $optionId = $this->selectedOptions[$group->id];
                    $option = $group->options->find($optionId);
                    if ($option) $this->optionsTotal += $option->price;
                }
            }
        }
    }

    public function addToCartFromModal(): void
    {
        if (!$this->selectedProduct) return;

        $product = $this->selectedProduct;
        $options = $this->selectedOptions;
        $notes = trim($this->notes); // Hilangkan spasi di awal/akhir catatan

        // Membuat ID unik untuk keranjang berdasarkan produk, opsi, dan catatan.
        // Jika catatan kosong, produk yang sama akan digabung.
        $cartId = md5($product->id . json_encode($options) . $notes);

        if ($this->cart->has($cartId)) {
            $item = $this->cart->get($cartId);
            $item['quantity']++;
            $this->cart->put($cartId, $item);
        } else {
            $optionsText = [];
            $optionsPrice = 0;

            foreach ($options as $groupId => $optionValue) {
                $group = $product->optionGroups->find($groupId);
                if ($group->type === 'radio') {
                    $option = $group->options->find($optionValue);
                    $optionsText[$group->name] = $option->name;
                    $optionsPrice += $option->price;
                } else {
                    $selected = [];
                    foreach ($optionValue as $optionId => $isSelected) {
                        if ($isSelected) {
                            $option = $group->options->find($optionId);
                            $selected[] = $option->name;
                            $optionsPrice += $option->price;
                        }
                    }
                    if (!empty($selected)) {
                        $optionsText[$group->name] = implode(', ', $selected);
                    }
                }
            }
            $this->cart->put($cartId, [
                'product_id'   => $product->id,
                'name'         => $product->name,
                'price'        => $product->selling_price,
                'options_price' => $optionsPrice,
                'quantity'     => 1,
                'options_text' => $optionsText,
                'options_raw'  => $options,
                'notes'        => $notes,
            ]);
        }

        $this->calculateTotal();
        $this->closeOptionsModal();
    }

    public function addToCart(Product $product): void
    {
        $cartId = md5($product->id . '[]'); // ID unik untuk produk tanpa opsi
        if ($this->cart->has($cartId)) {
            $item = $this->cart->get($cartId);
            $item['quantity']++;
            $this->cart->put($cartId, $item);
        } else {
            $this->cart->put($cartId, [
                'product_id'   => $product->id,
                'name'         => $product->name,
                'price'        => $product->selling_price,
                'options_price' => 0,
                'quantity'     => 1,
                'options_text' => [],
                'options_raw'  => [],
                'notes'        => '',
            ]);
        }
        $this->calculateTotal();
    }

    public function closeOptionsModal(): void
    {
        $this->showOptionsModal = false;
        $this->reset('selectedProduct', 'selectedOptions', 'notes', 'optionsTotal');
    }

    public function updateQuantity(string $cartId, string $type): void
    {
        if (!$this->cart->has($cartId)) return;

        $item = $this->cart->get($cartId);

        if ($type === 'increment') {
            $product = Product::find($item['product_id']);
            if ($item['quantity'] + 1 > $product->stock) {
                Notification::make()->title('Jumlah melebihi stok!')->warning()->send();
                return;
            }
            $item['quantity']++;
            $this->cart->put($cartId, $item);
        } elseif ($type === 'decrement') {
            if ($item['quantity'] > 1) {
                $item['quantity']--;
                $this->cart->put($cartId, $item);
            } else {
                $this->cart->forget($cartId); // Hapus item jika kuantitasnya 1
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
                        'product_id'        => $item['product_id'],
                        'product_name'      => $item['name'],
                        'quantity'          => $item['quantity'],
                        'unit_price'        => $item['price'] + $item['options_price'],
                        'total_price'       => ($item['price'] + $item['options_price']) * $item['quantity'],
                        'selected_options'  => $item['options_raw'], // Simpan data mentah opsi
                        'notes'             => $item['notes'],       // Simpan catatan
                    ]);
                    Product::find($item['product_id'])->decrement('stock', $item['quantity']);
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
