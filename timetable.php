<?php
require_once 'config/config.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = '시간표';
include 'includes/header.php';
?>

<link href="assets/css/timetable.css" rel="stylesheet">

<div class="row">
    <div class="col-12">
        <h2 class="mb-4">
            <i class="fas fa-calendar-alt me-2"></i>시간표 조회
        </h2>
        
        <!-- 검색 폼 -->
        <div class="card mb-4">
            <div class="card-body">
                <form id="timetableSearchForm">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="schoolName" class="form-label">학교 이름</label>
                            <input type="text" class="form-control" id="schoolName" 
                                   placeholder="전체 학교 이름" required>
                        </div>
                        <div class="col-md-3">
                            <label for="grade" class="form-label">학년</label>
                            <select class="form-select" id="grade" required>
                                <option value="">선택</option>
                                <option value="1">1학년</option>
                                <option value="2">2학년</option>
                                <option value="3">3학년</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="classNum" class="form-label">반</label>
                            <input type="number" class="form-control" id="classNum" 
                                   placeholder="반" min="1" max="20" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>시간표 조회
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- 로딩 표시 -->
        <div id="loadingIndicator" class="text-center d-none mb-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">시간표를 불러오는 중... (약 20초 소요)</p>
        </div>
        
        <!-- 에러 메시지 -->
        <div id="errorMessage" class="alert alert-danger d-none" role="alert"></div>
        
        <!-- 시간표 표시 영역 -->
        <div id="timetableResult"></div>
        
        <!-- 갱신 버튼 영역 -->
        <div id="refreshSection" class="d-none mt-3">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-1">
                                <i class="fas fa-clock me-2"></i>최근 업데이트: 
                                <span id="lastUpdateTime" class="text-muted">-</span>
                            </h6>
                            <p class="mb-0 small text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                갱신은 약 20초 정도 소요됩니다.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button id="refreshBtn" class="btn btn-outline-primary">
                                <i class="fas fa-sync-alt me-2"></i>시간표 갱신
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const NEIS_API_KEY = 'baccefe5de134f29becc613503c069f5';
let currentSearchData = null;

// 조회 버튼 - DB에서 가져오기
document.getElementById('timetableSearchForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const schoolName = document.getElementById('schoolName').value.trim();
    const grade = document.getElementById('grade').value;
    const classNum = document.getElementById('classNum').value;
    
    if (!schoolName || !grade || !classNum) {
        showError('모든 항목을 입력해주세요.');
        return;
    }
    
    currentSearchData = { schoolName, grade, classNum };
    
    showLoading(true);
    hideError();
    document.getElementById('timetableResult').innerHTML = '';
    document.getElementById('refreshSection').classList.add('d-none');
    
    try {
        const formData = new FormData();
        formData.append('schoolName', schoolName);
        formData.append('grade', grade);
        formData.append('classNum', classNum);
        
        const response = await fetch('ajax/get_timetable_from_db.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (!result.success) {
            showError(result.message);
            // 에러나도 갱신 버튼 표시
            document.getElementById('refreshSection').classList.remove('d-none');
            document.getElementById('lastUpdateTime').textContent = '데이터 없음';
            return;
        }
        
        // 시간표 표시
        displayWeeklyTimetableFromDb(result.schoolName, grade, classNum, result.timetable, result.lastUpdate);
        
    } catch (error) {
        console.error('Error:', error);
        showError('시간표를 불러오는 중 오류가 발생했습니다: ' + error.message);
    } finally {
        showLoading(false);
    }
});

// 갱신 버튼 - API 호출
document.getElementById('refreshBtn').addEventListener('click', async function() {
    if (!currentSearchData) return;
    
    if (!confirm('시간표를 갱신하시겠습니까?\n약 20초 정도 소요됩니다.')) {
        return;
    }
    
    const btn = this;
    const icon = btn.querySelector('i');
    btn.disabled = true;
    icon.classList.add('fa-spin');
    
    showLoading(true);
    hideError();
    
    try {
        // 1. 학교 정보 조회
        const schoolInfo = await getSchoolInfo(currentSearchData.schoolName);
        if (!schoolInfo) {
            showError(`'${currentSearchData.schoolName}' 학교 정보를 찾을 수 없습니다. 학교 이름을 정확히 입력해주세요.`);
            return;
        }
        
        // 2. 이번 주 시간표 조회 (월~금)
        const timetable = await getWeeklyTimetable(schoolInfo, currentSearchData.grade, currentSearchData.classNum);
        if (!timetable) {
            showError(`${schoolInfo.fullSchoolName} ${currentSearchData.grade}학년 ${currentSearchData.classNum}반의 시간표 정보가 없습니다.`);
            return;
        }
        
        // 3. DB에 저장
        await saveTimetableToDb(schoolInfo, currentSearchData.grade, currentSearchData.classNum, timetable.rawData);
        
        // 4. 시간표 표시
        displayWeeklyTimetable(schoolInfo, currentSearchData.grade, currentSearchData.classNum, timetable);
        
        alert('시간표가 성공적으로 갱신되었습니다!');
        
    } catch (error) {
        console.error('Error:', error);
        showError('시간표 갱신 중 오류가 발생했습니다: ' + error.message);
    } finally {
        showLoading(false);
        btn.disabled = false;
        icon.classList.remove('fa-spin');
    }
});

async function getSchoolInfo(schoolName) {
    const url = `https://open.neis.go.kr/hub/schoolInfo?KEY=${NEIS_API_KEY}&Type=json&pIndex=1&pSize=10&SCHUL_NM=${encodeURIComponent(schoolName)}`;
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (!data.schoolInfo || !data.schoolInfo[1] || !data.schoolInfo[1].row) {
            console.error('학교 정보 없음:', data);
            return null;
        }
        
        const schoolData = data.schoolInfo[1].row[0];
        return {
            officeCode: schoolData.ATPT_OFCDC_SC_CODE,
            schoolCode: schoolData.SD_SCHUL_CODE,
            schoolType: schoolData.SCHUL_KND_SC_NM,
            fullSchoolName: schoolData.SCHUL_NM
        };
    } catch (error) {
        console.error('getSchoolInfo 오류:', error);
        throw error;
    }
}

async function getWeeklyTimetable(schoolInfo, grade, classNum) {
    // 학교 유형에 따라 API 엔드포인트 결정
    let timetableType;
    if (schoolInfo.schoolType.includes('고등학교')) {
        timetableType = 'hisTimetable';
    } else if (schoolInfo.schoolType.includes('초등학교')) {
        timetableType = 'elsTimetable';
    } else {
        timetableType = 'misTimetable'; // 중학교
    }
    
    // 이번 주 월요일 날짜 계산
    const today = new Date();
    const dayOfWeek = today.getDay(); // 0(일) ~ 6(토)
    const monday = new Date(today);
    
    // 월요일로 이동
    if (dayOfWeek === 0) { // 일요일
        monday.setDate(today.getDate() + 1);
    } else if (dayOfWeek !== 1) { // 월요일이 아니면
        monday.setDate(today.getDate() - (dayOfWeek - 1));
    }
    
    const allData = [];
    
    // 월~금 시간표 가져오기
    for (let i = 0; i < 5; i++) {
        const currentDate = new Date(monday);
        currentDate.setDate(monday.getDate() + i);
        
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, '0');
        const date = String(currentDate.getDate()).padStart(2, '0');
        const ymd = `${year}${month}${date}`;
        
        const url = `https://open.neis.go.kr/hub/${timetableType}?KEY=${NEIS_API_KEY}&Type=json&pIndex=1&pSize=100&ATPT_OFCDC_SC_CODE=${schoolInfo.officeCode}&SD_SCHUL_CODE=${schoolInfo.schoolCode}&ALL_TI_YMD=${ymd}&GRADE=${grade}&CLASS_NM=${classNum}`;
        
        try {
            const response = await fetch(url);
            const data = await response.json();
            
            // 에러 체크
            if (data.RESULT && data.RESULT.CODE === 'INFO-200') {
                continue; // 데이터 없음
            }
            
            if (!data[timetableType] || !data[timetableType][1] || !data[timetableType][1].row) {
                continue;
            }
            
            const dayData = data[timetableType][1].row;
            dayData.forEach(item => {
                allData.push({
                    dayNum: i + 1, // 1(월) ~ 5(금)
                    period: parseInt(item.PERIO),
                    subject: item.ITRT_CNTNT.replace(/SLAT/g, '창의적 체험활동')
                });
            });
            
        } catch (error) {
            console.error(`${i+1}일차 시간표 조회 오류:`, error);
        }
    }
    
    if (allData.length === 0) {
        return null;
    }
    
    return convertToWeeklyTimetable(allData);
}

function convertToWeeklyTimetable(data) {
    const timetable = {
        days: ['월', '화', '수', '목', '금'],
        periods: [],
        rawData: data // DB 저장용 원본 데이터
    };
    
    // 최대 교시 찾기
    let maxPeriod = 0;
    data.forEach(item => {
        if (item.period > maxPeriod) {
            maxPeriod = item.period;
        }
    });
    
    // 교시별로 정리
    for (let period = 1; period <= maxPeriod; period++) {
        const periodData = {
            period: period,
            subjects: ['', '', '', '', ''] // 월화수목금
        };
        
        data.forEach(item => {
            if (item.period === period) {
                const dayIndex = item.dayNum - 1; // 0(월) ~ 4(금)
                if (dayIndex >= 0 && dayIndex < 5) {
                    periodData.subjects[dayIndex] = item.subject;
                }
            }
        });
        
        timetable.periods.push(periodData);
    }
    
    return timetable;
}

async function saveTimetableToDb(schoolInfo, grade, classNum, timetableData) {
    try {
        const response = await fetch('ajax/save_timetable.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                schoolInfo: schoolInfo,
                grade: grade,
                classNum: classNum,
                timetableData: timetableData
            })
        });
        
        const result = await response.json();
        console.log('DB 저장:', result);
        return result.success;
    } catch (error) {
        console.error('DB 저장 실패:', error);
        return false;
    }
}

function displayWeeklyTimetableFromDb(schoolName, grade, classNum, timetable, lastUpdate) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const date = String(now.getDate()).padStart(2, '0');
    
    let html = `
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-school me-2"></i>${schoolName} ${grade}학년 ${classNum}반
                </h4>
                <p class="mb-0 mt-2">
                    <i class="fas fa-calendar me-2"></i>${year}년 ${month}월 ${date}일 기준 이번 주 시간표
                </p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover timetable-weekly">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 80px;">교시</th>
    `;
    
    // 요일 헤더
    timetable.days.forEach(day => {
        html += `<th class="text-center">${day}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    // 교시별 데이터
    timetable.periods.forEach(periodData => {
        html += `
            <tr>
                <td class="text-center fw-bold bg-light">${periodData.period}교시</td>
        `;
        
        periodData.subjects.forEach(subject => {
            const displaySubject = subject || '-';
            html += `<td class="text-center">${displaySubject}</td>`;
        });
        
        html += '</tr>';
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('timetableResult').innerHTML = html;
    
    // 갱신 섹션 표시
    document.getElementById('refreshSection').classList.remove('d-none');
    document.getElementById('lastUpdateTime').textContent = lastUpdate;
}

function displayWeeklyTimetable(schoolInfo, grade, classNum, timetable) {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const date = String(now.getDate()).padStart(2, '0');
    const time = now.toLocaleTimeString('ko-KR');
    
    let html = `
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="fas fa-school me-2"></i>${schoolInfo.fullSchoolName} ${grade}학년 ${classNum}반
                </h4>
                <p class="mb-0 mt-2">
                    <i class="fas fa-calendar me-2"></i>${year}년 ${month}월 ${date}일 기준 이번 주 시간표
                </p>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover timetable-weekly">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 80px;">교시</th>
    `;
    
    // 요일 헤더
    timetable.days.forEach(day => {
        html += `<th class="text-center">${day}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    // 교시별 데이터
    timetable.periods.forEach(periodData => {
        html += `
            <tr>
                <td class="text-center fw-bold bg-light">${periodData.period}교시</td>
        `;
        
        periodData.subjects.forEach(subject => {
            const displaySubject = subject || '-';
            html += `<td class="text-center">${displaySubject}</td>`;
        });
        
        html += '</tr>';
    });
    
    html += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('timetableResult').innerHTML = html;
    
    // 갱신 섹션 표시 (갱신 직후)
    document.getElementById('refreshSection').classList.remove('d-none');
    document.getElementById('lastUpdateTime').textContent = `방금 전 (${time})`;
}

function showLoading(show) {
    const indicator = document.getElementById('loadingIndicator');
    if (show) {
        indicator.classList.remove('d-none');
    } else {
        indicator.classList.add('d-none');
    }
}

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.textContent = message;
    errorDiv.classList.remove('d-none');
}

function hideError() {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.classList.add('d-none');
}
</script>

<?php include 'includes/footer.php'; ?>
