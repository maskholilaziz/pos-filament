## Manajemen Menu & Inventaris Tingkat Lanjut

Ini adalah pengembangan dari apa yang sudah kita mulai.

Paket Combo (Product Bundling)

Kasus: Membuat menu "Paket Kenyang" (1 Nasi Goreng + 1 Es Teh) dengan harga spesial.

Logika: Saat "Paket Kenyang" terjual, sistem harus secara otomatis mengurangi stok untuk 1 porsi Nasi Goreng dan 1 gelas Es Teh. Ini membutuhkan relasi many-to-many antara produk (paket) dan komponennya (item individual).

Manajemen Inventaris Berbasis Bahan Baku

Kasus: Saat menjual "1 Cappucino", sistem tidak mengurangi stok "Cappucino", melainkan mengurangi stok bahan bakunya: 1 shot Espresso (misal: 7gr biji kopi) dan 150ml Susu.

Logika: Setiap produk memiliki "resep" yang terdaftar di sistem. Ini adalah fitur paling kompleks tapi paling penting untuk mengontrol HPP (Harga Pokok Penjualan) dan melakukan pemesanan bahan baku secara akurat.

## Manajemen Operasional & Dapur

Ini adalah fitur yang menghubungkan kasir dengan tim dapur dan operasional di lantai.

Manajemen Meja (Table Management)

Kasus: Pelanggan duduk di Meja 05, memesan, lalu ingin menambah pesanan lagi nanti.

Logika: Kasir bisa membuka "tab" untuk setiap meja. Pesanan akan ditambahkan ke meja tersebut, bukan langsung dibayar. Pembayaran dilakukan nanti setelah pelanggan selesai makan. Fitur ini juga mencakup pindah meja atau gabung meja.

Kitchen Display System (KDS) / Pencetakan Dapur

Kasus: Pesanan dari kasir harus langsung tampil di layar monitor atau dicetak oleh printer di dapur.

Logika: Setelah pesanan dikonfirmasi, data pesanan (lengkap dengan opsi dan catatan seperti "Pedas" atau "Telur dipisah") dikirim ke perangkat lain (KDS atau printer). Tim dapur bisa menandai pesanan sebagai "sedang dimasak" atau "selesai".

Tipe Pesanan

Kasus: Membedakan antara pesanan yang Makan di Tempat (Dine-In), Bawa Pulang (Takeaway), atau pesanan dari aplikasi Online (GoFood/GrabFood).

Logika: Setiap tipe pesanan bisa memiliki alur atau bahkan harga yang berbeda. Laporan penjualan nantinya bisa dipisah berdasarkan tipe pesanan ini untuk analisis.

## Manajemen Pelanggan & Pembayaran

Fokusnya adalah meningkatkan layanan dan fleksibilitas saat transaksi.

Program Loyalitas (Loyalty Program)

Kasus: Pelanggan yang sama datang berulang kali. Kita ingin memberikan reward.

Logika: Sistem menyimpan data pelanggan (via nomor HP). Setiap transaksi akan menghasilkan poin. Poin bisa ditukar dengan diskon atau produk gratis. Contoh lain: promo "Beli 5 Kopi, Gratis 1".

Diskon & Promosi Fleksibel

Kasus: Memberikan diskon 20% khusus untuk menu "Minuman" selama Happy Hour (jam 4-6 sore).

Logika: Sistem bisa mengelola berbagai skema diskon: berdasarkan persentase, nominal, per item, atau total tagihan. Ada juga promo yang aktif otomatis berdasarkan waktu atau hari.

Pisah Tagihan (Split Bill)

Kasus: Satu meja berisi 5 orang ingin membayar tagihan masing-masing.

Logika: Kasir bisa memilih item mana saja yang akan dibayar oleh orang pertama, lalu sisanya oleh orang kedua, dan seterusnya. Ada juga opsi "bagi rata" untuk membagi total tagihan secara merata.

sip, berjalan sesuai dengan harapan

apakah perlu komplit tiap pesanan? Misal pesanan "11" Thai Tea, Nasi Goreng Babat, Pop Corn Keju

Pesanan yang sudah diantar Thai Tea

jadi di sistem juga mencatat Thai Tea sudah selesai diantar, dan jika sudah selesai semua maka "11" bisa digunakan di pesanan selanjutnya
