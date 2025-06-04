<?php

declare(strict_types=1);

echo "Creating sample rooms...\n";

// Get database connection from parent scope
if (!isset($pdo)) {
    require_once __DIR__ . '/../../lib/db.php';
}

// Insert sample rooms
$sampleRooms = [
    // Conference Rooms (Building A, 2nd and 3rd Floor)
    ['Conference Room A', 1, 1, 2, 20, 'Large conference room with projector and whiteboard', 'Projector, Whiteboard, Video Conferencing, Air Conditioning', null, '["projector", "whiteboard", "video_conferencing", "air_conditioning"]', true],
    ['Conference Room B', 1, 1, 2, 15, 'Medium conference room with smart board', 'Smart Board, Projector, Sound System', null, '["smart_board", "projector", "sound_system"]', true],
    ['Executive Conference Room', 1, 1, 3, 12, 'Premium conference room for executive meetings', 'Large Display, Video Conferencing, Premium Furniture', null, '["large_display", "video_conferencing", "premium_furniture"]', true],

    // Meeting Rooms (Building A, 1st, 2nd, and 3rd Floor)
    ['Meeting Room 101', 2, 1, 1, 8, 'Small meeting room for team discussions', 'Whiteboard, TV Display', null, '["whiteboard", "tv_display"]', true],
    ['Meeting Room 102', 2, 1, 1, 6, 'Cozy meeting space for small teams', 'Whiteboard, Flip Chart', null, '["whiteboard", "flip_chart"]', true],
    ['Meeting Room 201', 2, 1, 2, 10, 'Medium meeting room with modern equipment', 'Smart TV, Wireless Presentation', null, '["smart_tv", "wireless_presentation"]', true],
    ['Meeting Room 301', 2, 1, 3, 12, 'Spacious meeting room with city view', 'Projector, Sound System, City View', null, '["projector", "sound_system", "city_view"]', true],

    // Training Rooms (Building B, 1st and 2nd Floor)
    ['Training Room Alpha', 3, 2, 5, 25, 'Large training room with flexible seating', 'Projector, Sound System, Movable Tables', null, '["projector", "sound_system", "movable_tables"]', true],
    ['Training Room Beta', 3, 2, 5, 20, 'Training room with computer access', 'Computers, Projector, Interactive Board', null, '["computers", "projector", "interactive_board"]', true],
    ['Training Room Gamma', 3, 2, 6, 30, 'Large training space for workshops', 'Multiple Projectors, Sound System, Workshop Tools', null, '["multiple_projectors", "sound_system", "workshop_tools"]', true],

    // Auditoriums (Building B)
    ['Main Auditorium', 4, 2, 5, 200, 'Large auditorium for events and presentations', 'Stage, Professional Sound System, Lighting, Large Screen', null, '["stage", "professional_sound", "lighting", "large_screen"]', true],
    ['Small Auditorium', 4, 2, 5, 80, 'Smaller auditorium for lectures', 'Projector, Sound System, Tiered Seating', null, '["projector", "sound_system", "tiered_seating"]', true],

    // Study Rooms (Building C, 1st and 2nd Floor)
    ['Study Room 1', 5, 3, 7, 4, 'Quiet study room for small groups', 'Whiteboard, Power Outlets', null, '["whiteboard", "power_outlets"]', true],
    ['Study Room 2', 5, 3, 7, 6, 'Study room with computer access', 'Computer, Printer, Whiteboard', null, '["computer", "printer", "whiteboard"]', true],
    ['Study Room 3', 5, 3, 8, 8, 'Larger study room for group projects', 'Large Table, Whiteboard, Flip Chart', null, '["large_table", "whiteboard", "flip_chart"]', true],

    // Labs (Building D, 3rd Floor)
    ['Computer Lab A', 6, 4, 9, 30, 'Computer lab with latest hardware', '30 Computers, Projector, Network Access', null, '["computers", "projector", "network_access"]', true],
    ['Computer Lab B', 6, 4, 9, 25, 'Programming lab with development tools', '25 Computers, Development Software, Large Displays', null, '["computers", "development_software", "large_displays"]', true],

    // Additional rooms
    ['Boardroom', 1, 1, 4, 10, 'Executive boardroom for important meetings', 'Premium Furniture, Video Conferencing, Catering Setup', null, '["premium_furniture", "video_conferencing", "catering_setup"]', true],
    ['Innovation Hub', 2, 4, 9, 15, 'Creative space for brainstorming and innovation', 'Whiteboards, Flexible Furniture, Collaboration Tools', null, '["whiteboards", "flexible_furniture", "collaboration_tools"]', true]
];

$insertedCount = 0;
foreach ($sampleRooms as $room) {
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO rooms (name, room_type_id, building_id, floor_id, capacity, description, equipment, image_url, features, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute($room);
    if ($stmt->rowCount() > 0) {
        $insertedCount++;
    }
}

echo "✅ Created $insertedCount sample rooms\n";
