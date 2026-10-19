<?php
require_once 'includes/auth.php';
requireRole(['superadmin']);

$pageTitle = "User Management - Dynamic Template Parser System";
$currentPage = "users";
$pageHeading = "User Management (Superadmin)";

$message = '';
$messageType = 'success';

$pdo = getDBConnection();

// Action handler for Add / Edit / Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'karyawan';
        
        if ($username && $password && $full_name) {
            if ($pdo) {
                try {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $hashed, $full_name, $role]);
                    $message = "User {$username} berhasil ditambahkan.";
                } catch (Exception $e) {
                    $message = "Gagal menambah user: " . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = "User baru ditambahkan di mode prototype (MySQL tidak terhubung).";
            }
        } else {
            $message = "Mohon lengkapi seluruh field!";
            $messageType = 'error';
        }
    } elseif ($action === 'delete') {
        $user_id = $_POST['user_id'] ?? 0;
        $currentUser = getCurrentUser();
        
        if ($user_id == $currentUser['id']) {
            $message = "Tidak dapat menghapus akun Anda sendiri!";
            $messageType = 'error';
        } else {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $message = "User berhasil dihapus.";
                } catch (Exception $e) {
                    $message = "Gagal menghapus user: " . $e->getMessage();
                    $messageType = 'error';
                }
            } else {
                $message = "User berhasil dihapus (mode prototype).";
            }
        }
    }
}

// Fetch users
$userList = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT id, username, full_name, role, created_at FROM users ORDER BY id ASC");
        $userList = $stmt->fetchAll();
    } catch (Exception $e) {
        $userList = array_values(getFallbackUsers());
    }
} else {
    $userList = array_values(getFallbackUsers());
}

include 'includes/header.php';
include 'includes/sidebar.php';
?>

<section class="page active" id="usersPage">
  <?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'error' ? 'danger' : 'success'; ?>" style="margin-bottom: 20px;">
      <span><?php echo htmlspecialchars($message); ?></span>
    </div>
  <?php endif; ?>

  <div class="toolbar" style="margin-bottom: 24px;">
    <div>
      <h2 style="margin: 0; font-size: 20px;">Kelola Akun Pengguna</h2>
      <p style="margin: 4px 0 0; color: var(--muted); font-size: 14px;">Tambah dan atur hak akses untuk Superadmin, Admin, dan Karyawan.</p>
    </div>
    <button class="primary-button" onclick="document.getElementById('newUserModal').classList.remove('hidden')">+ Tambah User Baru</button>
  </div>

  <section class="content-card table-card">
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Nama Lengkap</th>
            <th>Username</th>
            <th>Kategori Role</th>
            <th>Tanggal Dibuat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($userList as $u): ?>
            <tr>
              <td>#<?php echo htmlspecialchars($u['id']); ?></td>
              <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
              <td><code><?php echo htmlspecialchars($u['username']); ?></code></td>
              <td>
                <?php if ($u['role'] === 'superadmin'): ?>
                  <span class="badge badge-purple" style="background:#8b5cf6; color:white; padding:4px 10px; border-radius:99px; font-weight:bold; font-size:12px;">Superadmin</span>
                <?php elseif ($u['role'] === 'admin'): ?>
                  <span class="badge badge-blue" style="background:#2563eb; color:white; padding:4px 10px; border-radius:99px; font-weight:bold; font-size:12px;">Admin</span>
                <?php else: ?>
                  <span class="badge badge-gray" style="background:#64748b; color:white; padding:4px 10px; border-radius:99px; font-weight:bold; font-size:12px;">Karyawan</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($u['created_at'] ?? '2026-09-22'); ?></td>
              <td>
                <?php if ($u['username'] !== 'superadmin' && $u['id'] != $_SESSION['user']['id']): ?>
                  <form method="POST" action="users.php" style="display:inline;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($u['id']); ?>">
                    <button type="submit" class="text-button danger">Hapus</button>
                  </form>
                <?php else: ?>
                  <small style="color:var(--muted)">Utama</small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</section>

<!-- Modal Tambah User -->
<div class="modal-backdrop hidden" id="newUserModal" role="dialog">
  <div class="modal" style="max-width: 480px; text-align: left;">
    <h2 style="margin-top: 0;">Tambah User Baru</h2>
    <form method="POST" action="users.php">
      <input type="hidden" name="action" value="create">
      <div style="display: grid; gap: 14px; margin-top: 16px;">
        <label>
          <span style="font-size: 13px; font-weight: 600; display:block; margin-bottom:4px;">Nama Lengkap</span>
          <input name="full_name" required placeholder="Contoh: Budi Santoso" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:10px;" />
        </label>
        <label>
          <span style="font-size: 13px; font-weight: 600; display:block; margin-bottom:4px;">Username</span>
          <input name="username" required placeholder="budi123" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:10px;" />
        </label>
        <label>
          <span style="font-size: 13px; font-weight: 600; display:block; margin-bottom:4px;">Password</span>
          <input type="password" name="password" required placeholder="••••••••" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:10px;" />
        </label>
        <label>
          <span style="font-size: 13px; font-weight: 600; display:block; margin-bottom:4px;">Kategori Role</span>
          <select name="role" style="width:100%; padding:10px; border:1px solid var(--border); border-radius:10px;">
            <option value="karyawan">Karyawan (Akses Generate Output saja)</option>
            <option value="admin">Admin (Akses Semua Fitur Parser)</option>
            <option value="superadmin">Superadmin (Akses Semua + Kelola User)</option>
          </select>
        </label>
      </div>

      <div class="modal-actions" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 10px;">
        <button class="secondary-button" type="button" onclick="document.getElementById('newUserModal').classList.add('hidden')">Batal</button>
        <button class="primary-button" type="submit">Simpan User</button>
      </div>
    </form>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
