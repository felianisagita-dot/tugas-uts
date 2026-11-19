<?php
require_once 'auth.php';
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 if(($_POST['aksi']??'')==='hapus'){$id=(int)$_POST['id'];$st=$koneksi->prepare("DELETE FROM kategori WHERE id_kategori=?");$st->bind_param('i',$id);$st->execute();$msg='Kategori dihapus.';}
 else {$nama=trim($_POST['nama_kategori']??'');if($nama!==''){$st=$koneksi->prepare("INSERT INTO kategori(nama_kategori) VALUES(?)");$st->bind_param('s',$nama);$st->execute();$msg='Kategori ditambahkan.';}else $msg='Nama kategori wajib diisi.';}
}
admin_header('Kelola Kategori');
if($msg): ?><div class="alert alert-info"><?= h($msg) ?></div><?php endif; ?>
<div class="card content-card p-3 mb-4"><form method="post" class="d-flex gap-2"><input class="form-control" name="nama_kategori" placeholder="Nama kategori baru" required><button class="btn btn-primary">Tambah</button></form></div>
<div class="card content-card p-3"><div class="table-responsive"><table class="table"><thead><tr><th>ID</th><th>Nama Kategori</th><th>Aksi</th></tr></thead><tbody>
<?php $q=$koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori");if($q):while($r=$q->fetch_assoc()):?><tr><td><?= h($r['id_kategori']) ?></td><td><?= h($r['nama_kategori']) ?></td><td><form method="post" onsubmit="return confirm('Hapus kategori?')" class="d-inline"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= h($r['id_kategori']) ?>"><button class="btn btn-sm btn-danger">Hapus</button></form></td></tr><?php endwhile;endif;?>
</tbody></table></div><small class="text-muted">Jika kategori masih dipakai produk, database mungkin menolak penghapusan. Pindahkan produk ke kategori lain terlebih dahulu.</small></div>
<?php admin_footer(); ?>
