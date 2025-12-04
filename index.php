<?php

session_start();
require_once 'includes/db.php';  // FIXED: Direct path
require_once 'controllers/ReviewController.php';

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php?id=1');
    exit;
}

$db = Database::getInstance();
$reviewController = new ReviewController();
$packageId = isset($_GET['id']) ? intval($_GET['id']) : 1;
$package = $db->querySingle("SELECT * FROM packages WHERE id = " . $packageId, true);
$isModerator = isset($_SESSION['user_id']) && $_SESSION['role'] === 'moderator';
$reviews = [];
$pendingReviews = [];
$pendingCount = 0;
$package = ['name' => 'PHP Package Manager', 'description' => 'Loading...'];
$totalReviewsCount = $reviewController->getTotalReviewsCount($packageId);
$randomReviews = $reviewController->getRandomReviews($packageId, 3);
$isModerator = isset($_SESSION['role']) && $_SESSION['role'] === 'moderator';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && isset($_SESSION['user_id'])) {
    $reviewController->addReview($packageId, $_SESSION['user_id'], $_POST['rating'], $_POST['comment']);
    header('Location: index.php?id=' . $packageId);
    exit;
}

try {
    $packageId = isset($_GET['id']) ? intval($_GET['id']) : 1;
    $package = $db->querySingle("SELECT * FROM packages WHERE id = $packageId", true) ?: $package;
    $reviews = $reviewController->getReviewsByPackage($packageId);
    $pendingCount = $reviewController->getPendingCount($packageId);
    $pendingReviews = $reviewController->getPendingReviews($packageId);
} catch (Exception $e) {
    // Silent fail - use defaults
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'moderate') {
    $reviewController->moderateReview($_POST['review_id'], $_SESSION['user_id'], $_POST['status']);
    header('Location: index.php?id=' . $packageId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $reviewController->addReview($packageId, 2, $_POST['rating'], $_POST['comment']); // user_id=2
    header('Location: index.php?id=' . $packageId);
    exit;
}

try {
    $packageId = isset($_GET['id']) ? intval($_GET['id']) : 1;
    $package = $db->querySingle("SELECT * FROM packages WHERE id = $packageId", true) ?: $package;
    $reviews = $reviewController->getReviewsByPackage($packageId);
    $pendingCount = $reviewController->getPendingCount($packageId);
    $pendingReviews = $reviewController->getPendingReviews($packageId);
} catch (Exception $e) {
    // Use defaults
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Package Review System - <?php echo htmlspecialchars($package['name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
<nav 
 class="bg-white shadow-lg border-b">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <h1 class="text-2xl font-bold text-gray-800">Package Reviews</h1>
            <div class="flex items-center space-x-4">
                <?php if (isset($_SESSION['username'])): ?>
                    <span class="text-sm text-gray-600">👋 <?php echo htmlspecialchars($_SESSION['username']); ?> 
                        <?php if ($_SESSION['role'] === 'moderator'): ?><span class="ml-1 text-xs bg-yellow-200 px-2 py-1 rounded-full">(Moderator)</span><?php endif; ?>
                    </span>
            

</div>

                    <?php if (isset($pendingCount) && $_SESSION['role'] === 'moderator'): ?>
                        <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm font-semibold">
                            <?php echo $pendingCount; ?> Pending
                        </span>
                    <?php endif; ?>
                    <a href="?logout=1" onclick="return confirm('Are you sure?')" 
                       class="bg-red-600 text-white px-6 py-2 rounded-xl hover:bg-red-700 font-semibold transition-all shadow-md">
                        Logout
                    </a>
                <?php else: ?>
                    <div class="flex space-x-3">
                        <a href="login.php" class="bg-blue-600 text-white px-5 py-2 rounded-xl hover:bg-blue-700 font-semibold transition-all shadow-md">Login</a>
                        <a href="signup.php" class="bg-green-600 text-white px-5 py-2 rounded-xl hover:bg-green-700 font-semibold transition-all shadow-md">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<div class="max-w-6xl mx-auto px-4 py-8">
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h2 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($package['name']); ?></h2>
            <p class="text-gray-600 text-lg"><?php echo htmlspecialchars($package['description']); ?></p>
        </div>

        <?php if (!$isModerator): ?>
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Leave a Review</h3>
            <form method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rating</label>
                    <select name="rating" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="5">★★★★★ (5/5)</option>
                        <option value="4">★★★★☆ (4/5)</option>
                        <option value="3">★★★☆☆ (3/5)</option>
                        <option value="2">★★☆☆☆ (2/5)</option>
                        <option value="1">★☆☆☆☆ (1/5)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Comment</label>
                    <textarea name="comment" rows="4" class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" required></textarea>
                </div>
                <button type="submit" name="submit_review" class="bg-blue-600 text-white px-8 py-3 rounded-lg hover:bg-blue-700 font-semibold">Submit Review</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Approved Reviews -->
        <div class="bg-white rounded-xl shadow-lg p-8 mb-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Approved Reviews (<?php echo count($reviews); ?>)</h3>
            <div id="approved-reviews">
                <?php foreach ($reviews as $review): ?>
                <div class="border-b pb-6 mb-6 last:border-b-0">
                    <div class="flex items-center mb-2">
                        <div class="flex">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="<?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-xl">★</span>
                            <?php endfor; ?>
                        </div>
                        <span class="ml-3 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($review['username']); ?></span>
                        <span class="ml-2 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                    </div>
                    <p class="text-gray-700"><?php echo htmlspecialchars($review['comment']); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($isModerator): ?>
        <!-- Moderator Panel -->
        <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-xl shadow-lg p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Moderation Panel (<?php echo $pendingCount; ?> Pending)</h3>
            <div id="pending-reviews">
                <?php 
                $pendingReviews = $reviewController->getPendingReviews($packageId);
                foreach ($pendingReviews as $review): 
                ?>
                <div class="bg-white rounded-lg p-6 mb-4 border-l-4 border-yellow-400">
                    <div class="flex justify-between items-start mb-3">
                        <div class="flex">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="<?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-xl">★</span>
                            <?php endfor; ?>
                        </div>
                        <div class="flex space-x-2">
                            <form method="POST" class="inline">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <input type="hidden" name="status" value="approved">
                                <input type="hidden" name="action" value="moderate">
                                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 text-sm font-medium">Approve</button>
                            </form>
                            <form method="POST" class="inline">
                                <input type="hidden" name="review_id" value="<?php echo $review['id']; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <input type="hidden" name="action" value="moderate">
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm font-medium">Reject</button>
                            </form>
                        </div>
                    </div>
                    <p class="text-gray-700"><?php echo htmlspecialchars($review['comment']); ?></p>
                    <p class="text-sm text-gray-500 mt-2"><?php echo htmlspecialchars($review['username']); ?> - <?php echo date('M j, Y H:i', strtotime($review['created_at'])); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <!-- Add this BEFORE moderator panel or at very bottom -->
<div class="bg-gradient-to-r from-purple-50 to-pink-100 rounded-2xl p-8 mt-12 border-4 border-purple-200 shadow-lg">
    <h3 class="text-2xl font-bold text-gray-900 mb-6 flex items-center">
        🎲 Random Reviews
        <span class="ml-3 bg-purple-200 text-purple-800 px-3 py-1 rounded-full text-sm font-semibold">
            <?php echo count($randomReviews); ?> shown
        </span>
    </h3>
    <div class="space-y-4">
        <?php if (empty($randomReviews)): ?>
            <div class="text-center py-12 text-gray-500">
                <p class="text-lg">No reviews yet 😢</p>
                <p class="text-sm mt-2">Be the first to review this package!</p>
            </div>
        <?php else: ?>
            <?php foreach ($randomReviews as $review): ?>
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border-l-4 border-purple-500">
                <div class="flex items-center mb-3">
                    <div class="flex mr-3">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="<?php echo $i <= $review['rating'] ? 'text-yellow-400' : 'text-gray-300'; ?> text-2xl">★</span>
                        <?php endfor; ?>
                    </div>
                    <div>
                        <div class="font-semibold text-gray-900"><?php echo htmlspecialchars($review['username']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                </div>
                <p class="text-gray-700 leading-relaxed"><?php echo htmlspecialchars($review['comment']); ?></p>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

    </div>

    <script>
        // Auto-refresh pending reviews for moderators
        <?php if ($isModerator): ?>
        setInterval(function() {
            fetch('?id=<?php echo $packageId; ?>&ajax=pending')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('pending-reviews').innerHTML = html;
                });
        }, 5000);
        <?php endif; ?>
$reviews = $reviewController->getReviewsByPackage($packageId);
if (!$reviews) {
    $reviews = []; // Make sure it's an array
}

        function showLogin() {
            alert('Login: moderator/moderator123 or user1/user123');
        }

        if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php?id=1');
    exit;
}
    </script>
</body>
</html>
