<?php
// Suppress any HTML output
ob_start();
require __DIR__ . '/db.php';
ob_end_clean();

// Set JSON header first
header('Content-Type: application/json');

try {
    // Check if reward table exists
    $table_check = $mysqli->query("SHOW TABLES LIKE 'reward'");
    if ($table_check->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Reward table does not exist',
            'rewards' => []
        ]);
        exit;
    }
    
    // Fix query to handle NULL values properly
    // is_active: NULL or 1 = active (default is 1, so NULL should be treated as 1)
    // quantity_available: Must be > 0 (default is 0, so we need explicit values > 0)
    $stmt = $mysqli->prepare('
      SELECT reward_id, reward_name, description, points_required, quantity_available, category, reward_image, is_active
      FROM reward
      WHERE (is_active IS NULL OR is_active = 1)
        AND (quantity_available IS NOT NULL AND quantity_available > 0)
      ORDER BY points_required ASC
    ');
    
    if (!$stmt) {
        throw new Exception('Database query preparation failed: ' . $mysqli->error);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $rewards = [];
    while ($row = $result->fetch_assoc()) {
      // Ensure all values are properly formatted
      $row['reward_id'] = (int)$row['reward_id'];
      $row['points_required'] = (int)$row['points_required'];
      $row['quantity_available'] = (int)$row['quantity_available'];
      $row['is_active'] = (int)$row['is_active'];
      $rewards[] = $row;
    }
    $stmt->close();
    
    // Debug: Check total rewards in database
    $total_check = $mysqli->query("SELECT COUNT(*) as total FROM reward");
    $total_row = $total_check->fetch_assoc();
    $total_rewards = $total_row['total'];
    
    // Check rewards that don't meet criteria
    $hidden_check = $mysqli->query("
        SELECT COUNT(*) as hidden 
        FROM reward 
        WHERE COALESCE(is_active, 0) != 1 OR COALESCE(quantity_available, 0) <= 0
    ");
    $hidden_row = $hidden_check->fetch_assoc();
    $hidden_rewards = $hidden_row['hidden'];
    
    // Return rewards data at root level for Flutter compatibility
    $response = [
        'success' => true,
        'message' => 'OK',
        'rewards' => $rewards,
        'count' => count($rewards),
        'debug' => [
            'total_in_database' => (int)$total_rewards,
            'visible_rewards' => count($rewards),
            'hidden_rewards' => (int)$hidden_rewards
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Return error in JSON format
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'rewards' => []
    ]);
}
exit;
?>
