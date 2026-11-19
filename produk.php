<?php
require_once 'auth.php';
$msg = '';
$uploadDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $aksi = $_POST['aksi'] ?? '';
    if ($aksi==='hapus') {
        $id=(int)($_POST['id_produk']??0);
        $st=$koneksi->prepare("DELETE FROM produk WHERE id_produk=?");
        $st->bind_param('i',$id); $st->execute();
        $msg='Produk berhasil dihapus.';
    } elseif ($aksi==='simpan') {
        $id=(int)($_POST['id_produk']??0);
        $nama=trim($_POST['nama_produk']??'');
        $harga=(float)($_POST['harga']??0);
        $stok=(int)($_POST['stok']??0);
        $kategori=(int)($_POST['id_kategori']??0);
        $deskripsi=trim($_POST['deskripsi']??'');
        $gambar=trim($_POST['gambar_lama']??'');
        if (!empty($_FILES['gambar']['name'])) {
            $ext=strtolower(pathinfo($_FILES['gambar']['name'],PATHINFO_EXTENSION));
            if (in_array($ext,['jpg','jpeg','png','webp']) && $_FILES['gambar']['size']<=3*1024*1024) {
                $namaFile='produk_'.bin2hex(random_bytes(6)).'.'.$ext;
                if (move_uploaded_file($_FILES['gambar']['tmp_name'],$uploadDir.$namaFile)) $gambar=$namaFile;
            }
        }
        if ($nama==='' || $harga<0 || $stok<0 || $kategori<1) $msg='Lengkapi nama, harga, stok, dan kategori dengan benar.';
        elseif ($id>0) {
            $st=$koneksi->prepare("UPDATE produk SET nama_produk=?,harga=?,stok=?,id_kategori=?,deskripsi=?,gambar=? WHERE id_produk=?");
            $st->bind_param('sdiissi',$nama,$harga,$stok,$kategori,$deskripsi,$gambar,$id); $st->execute(); $msg='Produk berhasil diperbarui.';
        } else {
            $st=$koneksi->prepare("INSERT INTO produk (nama_produk,harga,stok,id_kategori,deskripsi,gambar) VALUES (?,?,?,?,?,?)");
            $st->bind_param('sdiiss',$nama,$harga,$stok,$kategori,$deskripsi,$gambar); $st->execute(); $msg='Produk berhasil ditambahkan.';
        }
    }
}
$edit=null;
if(isset($_GET['edit'])) { $id=(int)$_GET['edit']; $st=$koneksi->prepare("SELECT * FROM produk WHERE id_produk=?"); $st->bind_param('i',$id); $st->execute(); $edit=$st->get_result()->fetch_assoc(); }
$kats=$koneksi->query("SELECT id_kategori,nama_kategori FROM kategori ORDER BY nama_kategori");
admin_header('Kelola Produk');
if($msg): ?><div class="alert alert-info"><?= h($msg) ?></div><?php endif; ?>
<div class="card content-card p-3 mb-4">
<h5><?= $edit?'Edit Produk':'Tambah Produk' ?></h5>
<form method="post" enctype="multipart/form-data" class="row g-3">
<input type="hidden" name="aksi" value="simpan"><input type="hidden" name="id_produk" value="<?= h($edit['id_produk']??0) ?>"><input type="hidden" name="gambar_lama" value="<?= h($edit['gambar']??'') ?>">
<div class="col-md-6"><label class="form-label">Nama produk</label><input required class="form-control" name="nama_produk" value="<?= h($edit['nama_produk']??'') ?>"></div>
<div class="col-md-3"><label class="form-label">Harga</label><input required min="0" type="number" class="form-control" name="harga" value="<?= h($edit['harga']??'') ?>"></div>
<div class="col-md-3"><label class="form-label">Stok</label><input required min="0" type="number" class="form-control" name="stok" value="<?= h($edit['stok']??'') ?>"></div>
<div class="col-md-6"><label class="form-label">Kategori</label><select required name="id_kategori" class="form-select"><option value="">Pilih kategori</option><?php if($kats): while($k=$kats->fetch_assoc()): ?><option value="<?= h($k['id_kategori']) ?>" <?= (int)($edit['id_kategori']??0)===(int)$k['id_kategori']?'selected':'' ?>><?= h($k['nama_kategori']) ?></option><?php endwhile; endif; ?></select></div>
<div class="col-md-6"><label class="form-label">Gambar (JPG/PNG/WEBP, maks. 3 MB)</label><input type="file" accept=".jpg,.jpeg,.png,.webp" class="form-control" name="gambar"></div>
<div class="col-12"><label class="form-label">Deskripsi</label><textarea class="form-control" name="deskripsi" rows="3"><?= h($edit['deskripsi']??'') ?></textarea></div>
<div class="col-12"><button class="btn btn-primary">Simpan Produk</button> <?php if($edit): ?><a class="btn btn-secondary" href="produk.php">Batal</a><?php endif; ?></div>
</form></div>
<div class="card content-card p-3"><h5>Daftar Produk</h5><div class="table-responsive"><table class="table table-striped"><thead><tr><th>Produk</th><th>Kategori</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr></thead><tbody>
<?php $q=$koneksi->query("SELECT p.*,k.nama_kategori FROM produk p LEFT JOIN kategori k ON p.id_kategori=k.id_kategori ORDER BY p.id_produk DESC"); if($q): while($r=$q->fetch_assoc()): ?>
<tr><td><?= h($r['nama_produk']) ?></td><td><?= h($r['nama_kategori']??'-') ?></td><td>Rp <?= number_format((float)$r['harga'],0,',','.') ?></td><td><?= h($r['stok']) ?></td><td><a class="btn btn-sm btn-warning" href="?edit=<?= h($r['id_produk']) ?>">Edit</a> <form method="post" class="d-inline" onsubmit="return confirm('Hapus produk ini?')"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id_produk" value="<?= h($r['id_produk']) ?>"><button class="btn btn-sm btn-danger">Hapus</button></form></td></tr>
<?php endwhile; endif; ?></tbody></table></div></div>
<?php admin_footer(); ?>
