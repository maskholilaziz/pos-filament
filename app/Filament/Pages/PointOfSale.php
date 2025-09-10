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

    public ?int $selectedCategory = null;
    public string $search = '';
    public Collection $cart;
    public float $total = 0.00;

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
        return Product::query()
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

    // == METHOD YANG DIPERBAIKI 1 ==
    public function addToCart(int $productId): void
    {
        $product = Product::find($productId);
        if (!$product || $product->stock <= 0) {
            Notification::make()->title('Stok produk habis!')->warning()->send();
            return;
        }

        if ($this->cart->has($productId)) {
            $item = $this->cart->get($productId); // Ambil item

            if ($item['quantity'] + 1 > $product->stock) {
                Notification::make()->title('Jumlah melebihi stok!')->warning()->send();
                return;
            }

            $item['quantity']++; // Ubah di variabel sementara
            $this->cart->put($productId, $item); // Masukkan kembali
        } else {
            $this->cart->put($productId, [
                'product_id' => $product->id,
                'name'       => $product->name,
                'price'      => $product->selling_price,
                'quantity'   => 1,
                'stock'      => $product->stock,
            ]);
        }
        $this->calculateTotal();
    }

    // == METHOD YANG DIPERBAIKI 2 ==
    public function updateQuantity(int $productId, string $type): void
    {
        if (!$this->cart->has($productId)) {
            return;
        }

        $item = $this->cart->get($productId); // Ambil item
        $product = Product::find($productId);

        if ($type === 'increment') {
            if ($item['quantity'] + 1 > $product->stock) {
                Notification::make()->title('Jumlah melebihi stok!')->warning()->send();
                return;
            }
            $item['quantity']++; // Ubah di variabel sementara
        } elseif ($type === 'decrement' && $item['quantity'] > 1) {
            $item['quantity']--; // Ubah di variabel sementara
        }

        $this->cart->put($productId, $item); // Masukkan kembali
        $this->calculateTotal();
    }

    public function removeFromCart(int $productId): void
    {
        $this->cart->forget($productId); // Gunakan method forget() untuk Collection
        $this->calculateTotal();
    }

    protected function calculateTotal(): void
    {
        $this->total = $this->cart->sum(fn($item) => $item['price'] * $item['quantity']);
    }

    public function clearCart(): void
    {
        $this->cart = collect();
        $this->calculateTotal();
    }

    protected function getActions(): array
    {
        return [
            'process_payment' => Action::make('process_payment')
                ->label('Proses Pembayaran')->color('success')->icon('heroicon-o-currency-dollar')->requiresConfirmation()->modalHeading('Konfirmasi Pembayaran')
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
                        'product_id'   => $item['product_id'],
                        'product_name' => $item['name'],
                        'quantity'     => $item['quantity'],
                        'unit_price'   => $item['price'],
                        'total_price'  => $item['price'] * $item['quantity'],
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
