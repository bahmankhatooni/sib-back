# مستندات API گزارشات

این مستند راهنمای استفاده از APIهای بخش گزارشات است.

## مسیرهای API

تمام مسیرهای زیر نیاز به احراز هویت دارند (`Authorization: Bearer {token}`).

Base URL: `/api/reports`

---

## 1. دریافت آمار کلی

**Endpoint:** `GET /api/reports/statistics`

**توضیحات:** دریافت آمار کلی کاربرگ‌ها شامل تعداد کل، تکمیل شده، ناتمام و آمار بر اساس واحد، هدف و برنامه.

**Query Parameters:**

| پارامتر | نوع | الزامی | توضیحات |
|---------|-----|--------|---------|
| `start_date` | string (date) | خیر | تاریخ شروع (فرمت: YYYY-MM-DD) |
| `end_date` | string (date) | خیر | تاریخ پایان (فرمت: YYYY-MM-DD) |
| `unit_id` | integer | خیر | شناسه واحد (فقط برای ادمین) |
| `target_id` | integer | خیر | شناسه هدف |
| `program_id` | integer | خیر | شناسه برنامه |

**نمونه درخواست:**

```bash
GET /api/reports/statistics?start_date=2026-01-01&end_date=2026-12-31&unit_id=1
```

**نمونه پاسخ موفق (200):**

```json
{
  "success": true,
  "data": {
    "summary": {
      "total_forms": 150,
      "completed_forms": 120,
      "incomplete_forms": 30,
      "completion_percentage": 80.00
    },
    "by_unit": [
      {
        "id": 1,
        "name": "واحد حقوقی",
        "total_forms": 50
      },
      {
        "id": 2,
        "name": "واحد مالی",
        "total_forms": 100
      }
    ],
    "by_target": [
      {
        "id": 1,
        "code": "5",
        "title": "ارتقاء کیفیت رسیدگی",
        "total_forms": 75
      }
    ],
    "by_program": [
      {
        "id": 1,
        "code": "4",
        "title": "شناسایی و رفع موارد اختلاف",
        "total_forms": 60
      }
    ]
  }
}
```

---

## 2. دریافت لیست کاربرگ‌ها (با فیلترهای پیشرفته)

**Endpoint:** `GET /api/reports/list`

**توضیحات:** دریافت لیست کاربرگ‌ها با قابلیت فیلتر و صفحه‌بندی برای گزارش‌گیری.

**Query Parameters:**

| پارامتر | نوع | الزامی | توضیحات |
|---------|-----|--------|---------|
| `per_page` | integer | خیر | تعداد آیتم در هر صفحه (پیش‌فرض: 10) |
| `search` | string | خیر | جستجو در کد و توضیحات |
| `start_date` | string (date) | خیر | تاریخ شروع (فرمت: YYYY-MM-DD) |
| `end_date` | string (date) | خیر | تاریخ پایان (فرمت: YYYY-MM-DD) |
| `unit_id` | integer | خیر | شناسه واحد (فقط برای ادمین) |
| `target_id` | integer | خیر | شناسه هدف |
| `program_id` | integer | خیر | شناسه برنامه |
| `task_id` | integer | خیر | شناسه اقدام |
| `activity_id` | integer | خیر | شناسه فعالیت |
| `is_completed` | boolean | خیر | وضعیت تکمیل (true/false) |

**نمونه درخواست:**

```bash
GET /api/reports/list?per_page=20&start_date=2026-01-01&is_completed=true&target_id=1
```

**نمونه پاسخ موفق (200):**

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "30201-4",
      "unit_id": 1,
      "target_id": 1,
      "program_id": 1,
      "task_id": 1,
      "activity_id": 1,
      "description": "فیلدهای ثابت...",
      "is_completed": true,
      "created_by": "admin",
      "created_at": "2026-06-01T10:00:00.000000Z",
      "updated_at": "2026-06-05T12:30:00.000000Z",
      "unit": {
        "id": 1,
        "name": "واحد حقوقی"
      },
      "target": {
        "id": 1,
        "code": "5",
        "title": "ارتقاء کیفیت رسیدگی"
      },
      "program": {
        "id": 1,
        "code": "4",
        "title": "شناسایی و رفع موارد اختلاف"
      },
      "task": {
        "id": 1,
        "code": "50401",
        "title": "استخراج موارد اختلاف"
      },
      "activity": {
        "id": 1,
        "title": "پیگیری و نظارت"
      }
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 20,
    "total": 100,
    "next_page_url": "http://localhost/api/reports/list?page=2",
    "prev_page_url": null
  }
}
```

---

## 3. دریافت جزئیات یک کاربرگ

**Endpoint:** `GET /api/reports/details/{id}`

**توضیحات:** دریافت جزئیات کامل یک کاربرگ شامل فیلدهای ثابت، فیلدهای متغیر و مقادیر ذخیره شده.

**نمونه درخواست:**

```bash
GET /api/reports/details/1
```

**نمونه پاسخ موفق (200):**

```json
{
  "success": true,
  "data": {
    "form": {
      "id": 1,
      "code": "30201-4",
      "unit_id": 1,
      "target_id": 1,
      "program_id": 1,
      "task_id": 1,
      "activity_id": 1,
      "description": "فیلدهای ثابت...",
      "is_completed": true,
      "created_by": "admin",
      "created_at": "2026-06-01T10:00:00.000000Z",
      "unit": {
        "id": 1,
        "name": "واحد حقوقی"
      },
      "target": {
        "id": 1,
        "code": "5",
        "title": "ارتقاء کیفیت رسیدگی"
      },
      "program": {
        "id": 1,
        "code": "4",
        "title": "شناسایی و رفع موارد اختلاف"
      },
      "task": {
        "id": 1,
        "code": "50401",
        "title": "استخراج موارد اختلاف"
      },
      "activity": {
        "id": 1,
        "title": "پیگیری و نظارت"
      }
    },
    "fields": [
      {
        "id": 1,
        "field_label": "فیلد متغیر 1",
        "field_type": "text",
        "field_placeholder": null,
        "field_options": null,
        "is_required": false,
        "sort_order": 0,
        "values": [
          {
            "id": 1,
            "field_value": "مقدار 1",
            "created_by": "admin",
            "created_at": "2026-06-01T10:00:00.000000Z"
          }
        ]
      },
      {
        "id": 2,
        "field_label": "فیلد متغیر 2",
        "field_type": "number",
        "field_placeholder": "عدد وارد کنید",
        "field_options": null,
        "is_required": true,
        "sort_order": 1,
        "values": [
          {
            "id": 2,
            "field_value": "100",
            "created_by": "user123",
            "created_at": "2026-06-02T14:20:00.000000Z"
          }
        ]
      }
    ]
  }
}
```

**خطاهای ممکن:**

- **404:** کاربرگ مورد نظر یافت نشد
- **403:** شما به این کاربرگ دسترسی ندارید

---

## 4. Export گزارش به Excel

**Endpoint:** `GET /api/reports/export`

**توضیحات:** دانلود فایل Excel حاوی گزارش کاربرگ‌ها با فیلترهای اعمال شده.

**Query Parameters:**

همان پارامترهای `/api/reports/list` را می‌پذیرد:

| پارامتر | نوع | الزامی | توضیحات |
|---------|-----|--------|---------|
| `start_date` | string (date) | خیر | تاریخ شروع (فرمت: YYYY-MM-DD) |
| `end_date` | string (date) | خیر | تاریخ پایان (فرمت: YYYY-MM-DD) |
| `unit_id` | integer | خیر | شناسه واحد (فقط برای ادمین) |
| `target_id` | integer | خیر | شناسه هدف |
| `program_id` | integer | خیر | شناسه برنامه |
| `task_id` | integer | خیر | شناسه اقدام |
| `activity_id` | integer | خیر | شناسه فعالیت |
| `is_completed` | boolean | خیر | وضعیت تکمیل (true/false) |
| `search` | string | خیر | جستجو در کد و توضیحات |

**نمونه درخواست:**

```bash
GET /api/reports/export?start_date=2026-01-01&end_date=2026-12-31&is_completed=true
```

**نمونه پاسخ:**

فایل Excel با نام `report_2026-06-08_14-30-00.xlsx` دانلود می‌شود.

**ساختار فایل Excel:**

| کد کاربرگ | واحد | کد هدف | عنوان هدف | کد برنامه | عنوان برنامه | کد اقدام | عنوان اقدام | عنوان فعالیت | وضعیت | تاریخ ایجاد | ایجاد کننده | فیلدهای متغیر و مقادیر |
|-----------|------|---------|-----------|----------|--------------|----------|-------------|--------------|--------|-------------|--------------|------------------------|
| 30201-4 | واحد حقوقی | 5 | ارتقاء کیفیت | 4 | شناسایی و رفع | 50401 | استخراج موارد | پیگیری و نظارت | تکمیل شده | 2026-06-01 10:00:00 | admin | فیلد 1: مقدار 1 \| فیلد 2: 100 |

---

## نکات مهم

### 1. احراز هویت

همه APIها نیاز به token دارند:

```bash
curl -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -X GET http://your-domain.com/api/reports/statistics
```

### 2. دسترسی‌ها

- **کاربران غیر ادمین:** فقط می‌توانند کاربرگ‌های واحد خودشان را ببینند
- **ادمین:** می‌تواند تمام کاربرگ‌ها را ببیند و بر اساس واحد فیلتر کند

### 3. فیلترهای تاریخ

فرمت تاریخ: `YYYY-MM-DD` (مثال: `2026-06-08`)

### 4. صفحه‌بندی

- پیش‌فرض: 10 آیتم در هر صفحه
- حداکثر: تا 100 آیتم در هر صفحه

---

## نمونه کدهای استفاده در JavaScript

### دریافت آمار:

```javascript
const getStatistics = async (filters = {}) => {
  const params = new URLSearchParams(filters);
  const response = await fetch(`/api/reports/statistics?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  return await response.json();
};

// استفاده:
const stats = await getStatistics({
  start_date: '2026-01-01',
  end_date: '2026-12-31',
  unit_id: 1
});
```

### دریافت لیست:

```javascript
const getReportsList = async (page = 1, filters = {}) => {
  const params = new URLSearchParams({
    ...filters,
    per_page: 20,
    page
  });
  
  const response = await fetch(`/api/reports/list?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Accept': 'application/json'
    }
  });
  return await response.json();
};

// استفاده:
const list = await getReportsList(1, {
  start_date: '2026-01-01',
  is_completed: true,
  target_id: 1
});
```

### دانلود گزارش:

```javascript
const downloadReport = async (filters = {}) => {
  const params = new URLSearchParams(filters);
  const response = await fetch(`/api/reports/export?${params}`, {
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });
  
  const blob = await response.blob();
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `report_${new Date().toISOString()}.xlsx`;
  a.click();
};

// استفاده:
await downloadReport({
  start_date: '2026-01-01',
  end_date: '2026-12-31'
});
```

---

## تست با cURL

### دریافت آمار:

```bash
curl -X GET "http://localhost/api/reports/statistics?start_date=2026-01-01&end_date=2026-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### دریافت لیست:

```bash
curl -X GET "http://localhost/api/reports/list?per_page=20&is_completed=true" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### دانلود گزارش:

```bash
curl -X GET "http://localhost/api/reports/export?start_date=2026-01-01" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output report.xlsx
```
