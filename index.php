<?php
// 1. INCLUDE HEADER & CHECK AUTH
$body_class = 'bg-light'; 
include 'includes/header.php'; 
include 'includes/check_auth.php'; 

$message = ''; // Variabel untuk notifikasi
$user_id = $_SESSION['user_id']; // Ambil ID user dari session


if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --- UNTUK MEMINJAM BUKU ---
    if (isset($_POST['borrow_book'])) {
        $book_id = $_POST['book_id'];
        $conn->begin_transaction();
        try {
           
            $stmt_stock = $conn->prepare("SELECT stock FROM books WHERE id = ? FOR UPDATE");
            $stmt_stock->bind_param("i", $book_id);
            $stmt_stock->execute();
            $stock_result = $stmt_stock->get_result()->fetch_assoc();
            if ($stock_result['stock'] < 1) throw new Exception("Stok buku habis!");
            $stmt_check = $conn->prepare("SELECT id FROM borrowings WHERE user_id = ? AND book_id = ? AND status = 'borrowed'");
            $stmt_check->bind_param("ii", $user_id, $book_id);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) throw new Exception("Anda sudah meminjam buku ini.");
            $due_date = date('Y-m-d', strtotime('+7 days'));
            $stmt_insert = $conn->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'borrowed')");
            $stmt_insert->bind_param("iis", $user_id, $book_id, $due_date);
            $stmt_insert->execute();
            $stmt_update_stock = $conn->prepare("UPDATE books SET stock = stock - 1 WHERE id = ?");
            $stmt_update_stock->bind_param("i", $book_id);
            $stmt_update_stock->execute();
            $conn->commit();
            $message = 'Buku berhasil dipinjam!';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Gagal meminjam: ' . $e->getMessage();
        }
    }

    // --- UNTUK MENGEMBALIKAN BUKU ---
    if (isset($_POST['return_book'])) {
        
        $borrow_id = $_POST['borrow_id'];
        $book_id = $_POST['book_id'];
        $conn->begin_transaction();
        try {
            $stmt_return = $conn->prepare("UPDATE borrowings SET return_date = CURDATE(), status = 'returned' WHERE id = ? AND user_id = ?");
            $stmt_return->bind_param("ii", $borrow_id, $user_id);
            $stmt_return->execute();
            if ($stmt_return->affected_rows === 0) throw new Exception("Data peminjaman tidak valid.");
            $stmt_stock_inc = $conn->prepare("UPDATE books SET stock = stock + 1 WHERE id = ?");
            $stmt_stock_inc->bind_param("i", $book_id);
            $stmt_stock_inc->execute();
            $conn->commit();
            $message = 'Buku berhasil dikembalikan!';
        } catch (Exception $e) {
            $conn->rollback();
            $message = 'Gagal mengembalikan: ' . $e->getMessage();
        }
    }
}

$search_term = $_GET['search'] ?? ''; 

?>

<?php if ($message) : ?>
    <div class="alert alert-dismissible fade show <?php echo strpos($message, 'Gagal') !== false ? 'alert-danger' : 'alert-success'; ?>" role="alert" style="border: none;">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


<div class="p-5 mb-4 bg-white rounded-3 shadow-sm border">
    <div class="container-fluid py-3">
        <h1 class="display-5 fw-bold">Selamat Datang, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h1>
        <p class="col-md-8 fs-4">Siap untuk petualangan literasi hari ini? Cari buku favoritmu.</p>
        
        <form action="index.php" method="GET" class="d-flex" role="search">
            <input class="form-control form-control-lg me-2" type="search" name="search" placeholder="Cari judul buku atau penulis..." aria-label="Search" value="<?php echo htmlspecialchars($search_term); ?>">
            <button class="btn btn-primary btn-lg" type="submit" style="min-width: 80px;">
                <i class="bi bi-search"></i>
            </button>
        </form>
    </div>
</div>

<h2 class="mb-3">Buku yang Sedang Anda Pinjam</h2>
<div class="row row-cols-1 g-4 mb-4">
    <?php
 
    $sql_my_books = "SELECT 
                        b.title, b.author, b.cover_image, b.read_link, 
                        br.due_date, 
                        br.id AS borrow_id, 
                        b.id AS book_id 
                    FROM borrowings br 
                    JOIN books b ON br.book_id = b.id 
                    WHERE br.user_id = ? AND br.status = 'borrowed'
                    ORDER BY br.due_date ASC";
    
    $stmt_my_books = $conn->prepare($sql_my_books);
    $stmt_my_books->bind_param("i", $user_id);
    $stmt_my_books->execute();
    $result_my_books = $stmt_my_books->get_result();

    if ($result_my_books->num_rows > 0) {
        while ($my_book = $result_my_books->fetch_assoc()) {
    ?>
        <div class="col">
            <div class="card shadow-sm card-book">
                <div class="row g-0">
                    <div class="col-md-2 col-4 d-flex align-items-center justify-content-center p-0">
                        <?php if (!empty($my_book['cover_image'])) : ?>
                            <img src="assets/img/covers/<?php echo htmlspecialchars($my_book['cover_image']); ?>" class="img-fluid rounded-start book-cover-small" alt="<?php echo htmlspecialchars($my_book['title']); ?>">
                        <?php else : ?>
                            <div class="book-cover-placeholder-small rounded-start">
                                <i class="bi bi-book"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-10 col-8">
                        <div class="card-body d-flex flex-column h-100">
                            <h5 class="card-title"><?php echo htmlspecialchars($my_book['title']); ?></h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($my_book['author']); ?></p>
                            <p class="card-text text-danger fw-bold">Jatuh Tempo: <?php echo date('d M Y', strtotime($my_book['due_date'])); ?></p>
                            
                            <div class="mt-auto">
                                <?php if (!empty($my_book['read_link'])) : ?>
                                    <a href="<?php echo htmlspecialchars($my_book['read_link']); ?>" target="_blank" class="btn btn-primary">
                                        <i class="bi bi-book-fill"></i> Baca Sekarang
                                    </a>
                                <?php endif; ?>

                                <form action="index.php" method="POST" class="d-inline-block ms-1">
                                    <input type="hidden" name="borrow_id" value="<?php echo $my_book['borrow_id']; ?>">
                                    <input type="hidden" name="book_id" value="<?php echo $my_book['book_id']; ?>">
                                    <button type="submit" name="return_book" class="btn btn-warning">Kembalikan</button>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
        } // akhir while
    } else {
        echo '<div class="col"><p class="text-center text-muted">Anda tidak sedang meminjam buku apapun.</p></div>';
    }
    $stmt_my_books->close();
    ?>
</div>


<hr class="my-4">

<h2 class="mb-3">
    <?php echo empty($search_term) ? 'Daftar Buku Tersedia' : 'Hasil Pencarian untuk "' . htmlspecialchars($search_term) . '"'; ?>
</h2>

<div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4">
    <?php
    // --- (Query search Anda tidak berubah) ---
    $sql_all_books = "SELECT * FROM books WHERE stock > 0";
    $params = [];
    $types = "";
    if (!empty($search_term)) {
        $sql_all_books .= " AND (title LIKE ? OR author LIKE ?)";
        $search_pattern = "%" . $search_term . "%"; 
        $params[] = &$search_pattern;
        $params[] = &$search_pattern;
        $types .= "ss"; 
    }
    $sql_all_books .= " ORDER BY title ASC";
    $stmt_all_books = $conn->prepare($sql_all_books);
    if (!empty($search_term)) {
        $stmt_all_books->bind_param($types, ...$params); 
    }
    $stmt_all_books->execute();
    $result_all_books = $stmt_all_books->get_result();
    
    if ($result_all_books->num_rows > 0) {
        while ($book = $result_all_books->fetch_assoc()) {
    ?>
            <div class="col">
                <div class="card h-100 shadow-sm card-book">
                    <?php if (!empty($book['cover_image'])) : ?>
                        <div class="book-cover-wrapper">
                            <img src="assets/img/covers/<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top book-cover-img" alt="<?php echo htmlspecialchars($book['title']); ?>">
                        </div>
                    <?php else : ?>
                        <div class="book-cover-placeholder card-img-top">
                            <i class="bi bi-book"></i>
                        </div>
                    <?php endif; ?>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($book['author']); ?></h6>
                        <p class="card-text small"><?php echo htmlspecialchars(substr($book['description'], 0, 80)) . '...'; ?></p>
                        
                        <form action="index.php" method="POST" class="mt-auto">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <div class="d-grid">
                                <button type="submit" name="borrow_book" class="btn btn-primary"><i class="bi bi-journal-plus"></i> Pinjam</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer" style="background-color: #fff; border-top: 1px solid #f0f0f0;">
                        <small class="text-muted">Kategori: <?php echo htmlspecialchars($book['category']); ?></small>
                        <small class="text-muted float-end fw-bold">Stok: <?php echo $book['stock']; ?></small>
                    </div>
                </div>
            </div>
    <?php
        } // akhir while
    } else {
        echo '<div class="col-12"><p class="text-center h5 text-muted mt-5">Oops! Tidak ada buku yang cocok dengan pencarian Anda.</p></div>';
    }
    $stmt_all_books->close();
    ?>
</div>

<?php

include 'includes/footer.php';
?>