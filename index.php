<?php
// REFERENCE: Sessions (Lecture)
session_start();

// 🔌 SECURE Database connection
require_once 'config/db.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ==========================================
// 🔍 SEARCH & FILTER ENGINE
// ==========================================
$search_query = "";
$category_filter = "";

// Base SQL: Only show future events, and JOIN to get the Organiser's name
$sql = "SELECT e.*, u.full_name AS organiser_name 
        FROM events e 
        JOIN users u ON e.organiser_id = u.user_id 
        WHERE e.event_date >= CURDATE()";
$params = [];
$types = "";

// Apply Search Filter
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $sql .= " AND e.title LIKE ?";
    $params[] = "%" . $search_query . "%";
    $types .= "s";
}

// Apply Category Filter
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category_filter = $_GET['category'];
    $sql .= " AND e.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$sql .= " ORDER BY e.event_date ASC";

// Execute secure query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events_result = $stmt->get_result();

// Fetch categories for the dropdown filter
$cat_result = $conn->query("SELECT * FROM event_categories ORDER BY category_name ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SU Events - Discover What's Happening</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f8f9fa; color: #333; }
        
        /* Navigation & Header */
        header { background: #004aad; color: white; padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; }
        header a { color: white; text-decoration: none; font-weight: bold; margin-left: 20px; }
        .hero { background: #004aad; color: white; padding: 60px 20px; text-align: center; }
        .hero h1 { font-size: 3rem; margin-bottom: 10px; }
        
        /* Search Bar */
        .search-container { max-width: 800px; margin: -30px auto 40px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); display: flex; gap: 10px; }
        .search-container input, .search-container select { flex: 1; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .btn-search { background: #ff6b6b; color: white; border: none; padding: 12px 25px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-search:hover { background: #e05c5c; }
        
        /* Eventbrite-Style Grid */
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .event-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 30px; }
        
        /* The Card */
        .card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 8px rgba(0,0,0,0.05); transition: transform 0.2s, box-shadow 0.2s; display: flex; flex-direction: column; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 8px 16px rgba(0,0,0,0.1); }
        
        .card-img-wrapper { height: 180px; background: #e9ecef; position: relative; }
        .card-img { width: 100%; height: 100%; object-fit: cover; }
        .card-badge { position: absolute; top: 15px; left: 15px; background: rgba(255, 255, 255, 0.9); padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; color: #004aad; }
        
        .card-body { padding: 20px; display: flex; flex-direction: column; flex-grow: 1; }
        .card-title { font-size: 1.25rem; margin-bottom: 10px; color: #1a1a1a; }
        .card-org { color: #666; font-size: 14px; margin-bottom: 15px; }
        .card-details { font-size: 14px; color: #555; margin-bottom: 20px; flex-grow: 1; line-height: 1.5; }
        
        .btn-details { display: block; text-align: center; background: #004aad; color: white; text-decoration: none; padding: 12px; border-radius: 6px; font-weight: bold; transition: background 0.2s; }
        .btn-details:hover { background: #003380; }
        .no-results { text-align: center; padding: 50px; color: #666; font-size: 1.2rem; }
    </style>
</head>
<body>
    <!-- Navbar for navigation -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/su_events_app/includes/navbar.php'; ?>
    
    <!-- Flash Messages (In case they login/logout from index) -->
    <?php include 'includes/flash.php'; ?>

    <!-- Navigation -->
     <!-- switched to dynamic navbar for each replacing header -->

    <!-- Hero Section -->
    <div class="hero">
        <h1>Discover Campus Life</h1>
        <p>Find and book tickets for the best society events at the university.</p>
    </div>

    <!-- Search & Filter Form -->
    <form class="search-container" method="GET" action="index.php">
        <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search_query); ?>">
        <select name="category">
            <option value="">All Categories</option>
            <?php while($cat = $cat_result->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($cat['category_name']); ?>" <?php echo ($category_filter === $cat['category_name']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['category_name']); ?>
                </option>
            <?php endwhile; ?>
        </select>
        <button type="submit" class="btn-search">Search</button>
    </form>

    <!-- Event Grid -->
    <div class="container">
        <?php if ($events_result->num_rows > 0): ?>
            <div class="event-grid">
                <?php while($event = $events_result->fetch_assoc()): ?>
                    <div class="card">
                        
                        <!-- Image & Badge -->
                        <div class="card-img-wrapper">
                            <?php if(!empty($event['image_path'])): ?>
                                <!-- IMPORTANT: Notice we don't need ../ here because index is in the root! -->
                                <img src="<?php echo htmlspecialchars($event['image_path']); ?>" alt="Event Image" class="card-img">
                            <?php else: ?>
                                <div style="width:100%; height:100%; display:flex; align-items:center; justify-content:center; color:#999; background:#e9ecef;">No Image</div>
                            <?php endif; ?>
                            <div class="card-badge"><?php echo htmlspecialchars($event['category']); ?></div>
                        </div>
                        
                        <!-- Content -->
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                            <div class="card-org">By <?php echo htmlspecialchars($event['society_name']); ?> (<?php echo htmlspecialchars($event['organiser_name']); ?>)</div>
                            
                            <div class="card-details">
                                📅 <?php echo date('D, M j, Y \a\t g:i A', strtotime($event['event_date'])); ?><br>
                                📍 <?php echo htmlspecialchars($event['location']) ?: 'TBD'; ?>
                            </div>
                            
                            <!-- Route to the detailed view -->
                            <a href="event_details.php?id=<?php echo $event['event_id']; ?>" class="btn-details">View Details</a>
                        </div>
                        
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <h2>No events found!</h2>
                <p>Try adjusting your search or check back later.</p>
                <a href="index.php" style="color: #004aad; display:inline-block; margin-top: 10px;">Clear Filters</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>
