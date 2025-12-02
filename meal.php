<?php
require_once 'config/config.php';

// 로그인 확인
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = '급식표';
include 'includes/header.php';

$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');

$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$startDayOfWeek = date('w', $firstDay);
?>

<link rel="stylesheet" href="assets/css/meal.css">

<div class="meal-container">
    <div class="meal-header">
        <h1><i class="fas fa-utensils me-3"></i>급식표</h1>
        <div class="user-info">
            <span class="badge bg-primary"><?php echo htmlspecialchars($_SESSION['real_name']); ?></span>
        </div>
    </div>

    <div class="month-navigation">
        <a href="?year=<?php echo $prevYear; ?>&month=<?php echo $prevMonth; ?>" class="btn-nav">
            <i class="fas fa-chevron-left"></i>
        </a>
        <h2 class="current-month"><?php echo $year; ?>년 <?php echo $month; ?>월</h2>
        <a href="?year=<?php echo $nextYear; ?>&month=<?php echo $nextMonth; ?>" class="btn-nav">
            <i class="fas fa-chevron-right"></i>
        </a>
    </div>

    <div class="calendar-wrapper">
        <table class="calendar-table">
            <thead>
                <tr>
                    <th>일</th>
                    <th>월</th>
                    <th>화</th>
                    <th>수</th>
                    <th>목</th>
                    <th>금</th>
                    <th>토</th>
                </tr>
            </thead>
            <tbody id="calendarBody">
                <?php
                $day = 1;
                $totalCells = ceil(($daysInMonth + $startDayOfWeek) / 7) * 7;
                
                for ($i = 0; $i < $totalCells; $i++) {
                    if ($i % 7 === 0) {
                        echo '<tr>';
                    }
                    
                    if ($i < $startDayOfWeek || $day > $daysInMonth) {
                        echo '<td class="empty-day"></td>';
                    } else {
                        $date = sprintf('%04d%02d%02d', $year, $month, $day);
                        $isToday = ($year == date('Y') && $month == date('n') && $day == date('j'));
                        $dayOfWeek = ($i % 7);
                        $isSunday = ($dayOfWeek === 0);
                        $isSaturday = ($dayOfWeek === 6);
                        
                        $classes = ['day-cell'];
                        if ($isToday) $classes[] = 'today';
                        if ($isSunday) $classes[] = 'sunday';
                        if ($isSaturday) $classes[] = 'saturday';
                        
                        echo '<td class="' . implode(' ', $classes) . '" data-date="' . $date . '">';
                        echo '<div class="day-number">' . $day . '</div>';
                        echo '<div class="meal-preview" id="meal-' . $date . '"></div>';
                        echo '</td>';
                        
                        $day++;
                    }
                    
                    if ($i % 7 === 6) {
                        echo '</tr>';
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="mealDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-day me-2"></i>
                    <span id="modalDate"></span> 급식
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalMealContent">
            </div>
        </div>
    </div>
</div>

<script>
const year = <?php echo $year; ?>;
const month = <?php echo $month; ?>;

const mealData = <?php
    $meals = [];
    $csvFile = __DIR__ . '/data/meal_data.csv';
    
    $userSchoolName = isset($_SESSION['school_name']) ? $_SESSION['school_name'] : '';
    
    if (file_exists($csvFile)) {
        $content = file_get_contents($csvFile);
        $lines = explode("\n", $content);
        
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $row = str_getcsv($line);
            
            if (count($row) >= 11) {
                $schoolName = $row[3];
                $mealType = $row[5];
                $date = $row[6];
                $dishes = $row[8];
                $calInfo = $row[10];
                
                if ($schoolName === $userSchoolName) {
                    $dishList = preg_split('/<br\s*\/?>/i', $dishes);
                    $dishList = array_map('trim', $dishList);
                    $dishList = array_filter($dishList, function($item) {
                        return !empty($item);
                    });
                    $dishList = array_values($dishList);
                    
                    if (!isset($meals[$date])) {
                        $meals[$date] = [];
                    }
                    
                    $meals[$date][$mealType] = [
                        'dishes' => $dishList,
                        'cal_info' => $calInfo
                    ];
                }
            }
        }
    }
    
    echo json_encode($meals, JSON_UNESCAPED_UNICODE);
?>;

document.addEventListener('DOMContentLoaded', function() {
    displayMeals();
});

function displayMeals() {
    for (const date in mealData) {
        const mealDiv = document.getElementById('meal-' + date);
        
        if (mealDiv) {
            const meal = mealData[date];
            mealDiv.innerHTML = formatMealPreview(meal);
            
            const cell = mealDiv.closest('.day-cell');
            if (cell) {
                cell.style.cursor = 'pointer';
                cell.onclick = () => showMealDetail(date, meal);
            }
        }
    }
}

function formatMealPreview(mealTypes) {
    let html = '<div class="meal-items">';
    
    const order = ['조식', '중식', '석식'];
    
    for (let mealType of order) {
        if (mealTypes[mealType]) {
            const meal = mealTypes[mealType];
            const dishes = meal.dishes.slice(0, 2);
            
            html += `<div class="meal-type-label">${mealType}</div>`;
            
            dishes.forEach(dish => {
                html += `<div class="meal-item">${dish}</div>`;
            });
            
            if (meal.dishes.length > 2) {
                html += `<div class="meal-more">외 ${meal.dishes.length - 2}개</div>`;
            }
        }
    }
    
    html += '</div>';
    return html;
}

function showMealDetail(date, mealTypes) {
    const year = date.substring(0, 4);
    const month = date.substring(4, 6);
    const day = date.substring(6, 8);
    
    document.getElementById('modalDate').textContent = `${year}년 ${parseInt(month)}월 ${parseInt(day)}일`;
    
    let html = '<div class="meal-detail">';
    
    const order = ['조식', '중식', '석식'];
    
    for (let mealType of order) {
        if (mealTypes[mealType]) {
            const meal = mealTypes[mealType];
            
            html += `<h6 class="mb-3"><i class="fas fa-bowl-rice me-2"></i>${mealType} 메뉴</h6>`;
            html += '<ul class="meal-list">';
            
            meal.dishes.forEach(dish => {
                html += `<li>${dish}</li>`;
            });
            
            html += '</ul>';
            
            if (meal.cal_info) {
                html += `<div class="cal-info mt-3"><i class="fas fa-fire me-2"></i>${meal.cal_info}</div>`;
            }
            
            if (mealType !== '석식' || (mealTypes['석식'] && order.indexOf(mealType) < order.length - 1)) {
                html += '<hr style="margin: 20px 0;">';
            }
        }
    }
    
    html += '</div>';
    
    document.getElementById('modalMealContent').innerHTML = html;
    
    const modal = new bootstrap.Modal(document.getElementById('mealDetailModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>

