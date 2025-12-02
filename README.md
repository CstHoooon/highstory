# 하이스토리(HighStory)

고등학생을 위한 시간표, 급식표, 학교 커뮤니티 서비스입니다.

## 주요 기능

- 시간표 조회: NEIS API를 활용한 실시간 시간표 조회 및 캐싱
- 급식표: 학교 급식 메뉴 확인
- 학교 커뮤니티: 게시판, 댓글, 좋아요 기능
- 사용자 인증: 학교별 회원가입 및 로그인

## 기술 스택

- Backend: PHP 7.4+
- Database: MySQL 5.7+
- Frontend: HTML5, CSS3, JavaScript (ES6+)
- Framework: Bootstrap 5.3
- Icons: Font Awesome 6.0

## 프로젝트 구조

```
highstory/
├── config/
│   ├── database.php      # 데이터베이스 연결 설정
│   └── config.php        # 전역 설정
├── includes/
│   ├── header.php        # 공통 헤더
│   └── footer.php        # 공통 푸터
├── assets/
│   ├── css/
│   │   ├── style.css     # 메인 스타일
│   │   ├── auth.css      # 인증 관련 스타일
│   │   ├── community.css # 커뮤니티 스타일
│   │   ├── meal.css      # 급식표 스타일
│   │   └── timetable.css # 시간표 스타일
│   └── js/
│       ├── main.js       # 메인 JavaScript
│       ├── auth.js       # 인증 관련 JavaScript
│       ├── login.js      # 로그인 JavaScript
│       └── community.js  # 커뮤니티 JavaScript
├── ajax/
│   ├── check_username.php         # 아이디 중복 확인
│   ├── get_timetable.php          # 시간표 조회
│   ├── get_timetable_from_db.php  # DB에서 시간표 조회
│   ├── like_post.php              # 게시글 좋아요
│   ├── refresh_timetable.php      # 시간표 갱신
│   ├── save_timetable.php         # 시간표 저장
│   ├── search_school.php          # 학교 검색
│   ├── write_comment.php          # 댓글 작성
│   └── write_post.php             # 게시글 작성
├── scripts/
│   └── update_timetable_cache.php # 시간표 캐시 자동 업데이트
├── sql/
│   └── schema.sql        # 데이터베이스 스키마
├── data/
│   └── meal_data.csv     # 급식 데이터
├── main.php              # 메인 페이지
├── login.php             # 로그인 페이지
├── signup.php            # 회원가입 페이지
├── logout.php            # 로그아웃 처리
├── community.php         # 커뮤니티 페이지
├── post.php              # 게시글 상세 페이지
├── timetable.php         # 시간표 페이지
├── meal.php              # 급식표 페이지
├── 404.php               # 404 오류 페이지
└── 500.php               # 500 오류 페이지
```

## 사용 방법

### 1. 회원가입

- 학교 선택
- 아이디, 비밀번호 입력
- 학번, 이름 입력

### 2. 로그인

- 학교 선택
- 아이디, 비밀번호 입력

### 3. 주요 기능

- 시간표: NEIS API를 통한 실시간 시간표 조회 및 갱신
- 급식표: 월별 급식 메뉴 확인 (조식/중식/석식)
- 커뮤니티: 게시글 작성, 댓글, 좋아요, 익명/실명 선택
