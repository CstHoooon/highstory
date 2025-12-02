# 하이스토리(HighStory)

고등학생을 위한 시간표, 급식표, 학교 커뮤니티 서비스입니다.

## 🚀 주요 기능

- **시간표 조회**: NEIS API를 활용한 실시간 시간표 조회 및 캐싱
- **급식표**: 학교 급식 메뉴 확인
- **학교 커뮤니티**: 게시판, 댓글, 좋아요 기능
- **사용자 인증**: 학교별 회원가입 및 로그인

## 🛠️ 기술 스택

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.0

## 📁 프로젝트 구조

```
EVERYTIME/
├── config/
│   ├── database.php      # 데이터베이스 연결 설정
│   └── config.php        # 전역 설정
├── includes/
│   ├── header.php        # 공통 헤더
│   └── footer.php        # 공통 푸터
├── assets/
│   ├── css/
│   │   ├── style.css     # 메인 스타일
│   │   └── auth.css      # 인증 관련 스타일
│   └── js/
│       ├── main.js       # 메인 JavaScript
│       └── auth.js       # 인증 관련 JavaScript
├── ajax/
│   └── check_username.php # 아이디 중복 확인
├── sql/
│   └── schema.sql        # 데이터베이스 스키마
├── main.php              # 메인 페이지
├── login.php             # 로그인 페이지
├── signup.php            # 회원가입 페이지
└── logout.php            # 로그아웃 처리
```

## 🗄️ 데이터베이스 설정

### 1. 테이블 생성
phpMyAdmin에서 `sql/schema.sql` 파일을 실행하여 테이블을 생성합니다.

### 2. 데이터베이스 연결 설정
`config/database.php` 파일에서 cafe24 데이터베이스 정보를 입력합니다. 데이터베이스명은 그대로 사용하고, 테이블명만 `every_` 접두사를 사용합니다.

```php
$host = 'localhost';
$dbname = 'badapage'; // 기존 데이터베이스 이름 그대로 사용
$username = 'your_username';
$password = 'your_password';
```

## 🎯 사용 방법

### 1. 회원가입
- 학교 선택
- 아이디, 비밀번호 입력
- 학번, 이름, 학년, 반 입력

### 2. 로그인
- 학교 선택
- 아이디, 비밀번호 입력

### 3. 주요 기능
- **시간표**: 과목별 시간표 등록 및 관리
- **성적계산기**: 성적 입력 및 평균 계산
- **커뮤니티**: 게시판 이용

## 🔧 개발 환경 설정

### 요구사항
- PHP 7.4 이상
- MySQL 5.7 이상
- Apache/Nginx 웹서버

### 설치 방법
1. 프로젝트 파일을 웹서버 루트 디렉토리에 업로드
2. 데이터베이스 설정 및 테이블 생성
3. `config/database.php`에서 데이터베이스 정보 수정
4. 웹브라우저에서 접속

## 📝 라이선스

이 프로젝트는 MIT 라이선스 하에 배포됩니다.

## 🤝 기여하기

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📞 문의

프로젝트에 대한 문의사항이 있으시면 이슈를 등록해주세요.
