<?php
require_once 'auth.php';
$produk = (int)($koneksi->query("SELECT COUNT(*) AS n FROM produk")->fetch_assoc()['n'] ?? 0);
$pembeli = (int)($koneksi->query("SELECT COUNT(*) AS n FROM users WHERE role='pembeli'")->fetch_assoc()['n'] ?? 0);
$pesanan = (int)($koneksi->query("SELECT COUNT(*) AS n FROM pesanan")->fetch_assoc()['n'] ?? 0);
$menunggu = (int)($koneksi->query("SELECT COUNT(*) AS n FROM pesanan WHERE status IN ('Menunggu','menunggu','Pending','pending')")->fetch_assoc()['n'] ?? 0);
admin_header('Dashboard');
?>
<div class="row g-3 mb-4">
 <?php foreach ([['Total Produk',$produk,'📦'],['Total Pembeli',$pembeli,'👥'],['Total Pesanan',$pesanan,'🧾'],['Pesanan Menunggu',$menunggu,'⏳']] as $item): ?>
 <div class="col-sm-6 col-xl-3"><div class="card content-card p-3"><div class="text-muted"><?= h($item[0]) ?></div><div class="d-flex justify-content-between align-items-center"><h2 class="mb-0"><?= $item[1] ?></h2><span class="fs-2"><?= $item[2] ?></span></div></div></div>
 <?php endforeach; ?>
</div>
<div class="card content-card p-3">
 <h5>Pesanan Terbaru</h5>
 <div class="table-responsive"><table class="table table-hover align-middle">
 <thead><tr><th>ID</th><th>Penerima</th><th>Tanggal</th><th>Total</th><th>Status</th><th></th></tr></thead><tbody>
 <?php
 $q = $koneksi->query("SELECT id_pesanan,nama_penerima,tanggal_pesanan,total_harga,status FROM pesanan ORDER BY id_pesanan DESC LIMIT 8");
 if ($q && $q->num_rows): while($r=$q->fetch_assoc()): ?>
 <tr><td>#<?= h($r['id_pesanan']) ?></td><td><?= h($r['nama_penerima']) ?></td><td><?= h($r['tanggal_pesanan']) ?></td><td>Rp <?= number_format((float)$r['total_harga'],0,',','.') ?></td><td><?= h($r['status']) ?></td><td><a class="btn btn-sm btn-outline-primary" href="pesanan.php">Kelola</a></td></tr>
 <?php endwhile; else: ?><tr><td colspan="6" class="text-center text-muted">Belum ada pesanan.</td></tr><?php endif; ?>
 </tbody></table></div>
</div>
<?php admin_footer(); ?>
