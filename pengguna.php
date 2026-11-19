<?php
require_once 'auth.php';
admin_header('Data Pengguna');
?>
<div class="card content-card p-3"><div class="table-responsive"><table class="table table-striped"><thead><tr><th>ID</th><th>Nama</th><th>Email</th><th>Role</th><th>Terdaftar</th></tr></thead><tbody>
<?php $q=$koneksi->query("SELECT id_user,nama,email,role,created_at FROM users ORDER BY id_user DESC");if($q):while($r=$q->fetch_assoc()):?><tr><td><?= h($r['id_user']) ?></td><td><?= h($r['nama']) ?></td><td><?= h($r['email']) ?></td><td><?= h($r['role']) ?></td><td><?= h($r['created_at']??'-') ?></td></tr><?php endwhile;endif;?>
</tbody></table></div></div>
<?php admin_footer(); ?>
