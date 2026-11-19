<?php
require_once 'auth.php';
$msg='';
$opsi=['Menunggu','Diproses','Dikirim','Selesai','Dibatalkan'];
if($_SERVER['REQUEST_METHOD']==='POST'){
 $id=(int)($_POST['id_pesanan']??0);$status=$_POST['status']??'';
 if(in_array($status,$opsi,true)){$st=$koneksi->prepare("UPDATE pesanan SET status=? WHERE id_pesanan=?");$st->bind_param('si',$status,$id);$st->execute();$msg='Status pesanan diperbarui.';}
}
admin_header('Kelola Pesanan');
if($msg): ?><div class="alert alert-success"><?= h($msg) ?></div><?php endif; ?>
<div class="card content-card p-3"><div class="table-responsive"><table class="table table-hover align-middle"><thead><tr><th>ID</th><th>Penerima</th><th>Alamat</th><th>No. HP</th><th>Pembayaran</th><th>Total</th><th>Status</th><th>Ubah</th></tr></thead><tbody>
<?php $q=$koneksi->query("SELECT * FROM pesanan ORDER BY id_pesanan DESC");if($q):while($r=$q->fetch_assoc()):?><tr><td>#<?= h($r['id_pesanan']) ?><br><small><?= h($r['tanggal_pesanan']) ?></small></td><td><?= h($r['nama_penerima']) ?></td><td><?= h($r['alamat']) ?></td><td><?= h($r['no_hp']) ?></td><td><?= h($r['metode_pembayaran']??'-') ?></td><td>Rp <?= number_format((float)$r['total_harga'],0,',','.') ?></td><td><?= h($r['status']) ?></td><td><form method="post" class="d-flex gap-1"><input type="hidden" name="id_pesanan" value="<?= h($r['id_pesanan']) ?>"><select name="status" class="form-select form-select-sm"><?php foreach($opsi as $o):?><option value="<?= h($o) ?>" <?= $r['status']===$o?'selected':'' ?>><?= h($o) ?></option><?php endforeach;?></select><button class="btn btn-sm btn-primary">Simpan</button></form></td></tr><?php endwhile;else:?><tr><td colspan="8">Belum ada pesanan.</td></tr><?php endif;?>
</tbody></table></div></div>
<?php admin_footer(); ?>
