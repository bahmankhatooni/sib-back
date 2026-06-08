# تغییرات بخش گزارشات - sib-back

## خلاصه تغییرات

بخش گزارشات (Reports) به سامانه sib-back اضافه شد. این بخش شامل APIهایی برای نمایش آمار، لیست کاربرگ‌ها با فیلترهای پیشرفته، جزئیات کاربرگ‌ها و export به Excel است.

---

## فایل‌های جدید

### 1. کنترلر گزارشات
**مسیر:** `app/Http/Controllers/Api/ReportController.php`

**متدها:**
- `statistics()`: دریافت آمار کلی کاربرگ‌ها (تعداد کل، تکمیل شده، ناتمام، آمار بر اساس واحد/هدف/برنامه)
- `list()`: لیست کاربرگ‌ها با فیلترهای پیشرفته و صفحه‌بندی
- `details($id)`: جزئیات کامل یک کاربرگ شامل فیلدها و مقادیر
- `export()`: export گزارش به فایل Excel

**ویژگی‌ها:**
- پشتیبانی از دسترسی‌های مختلف (ادمین و کاربر عادی)
- فیلترهای متنوع (تاریخ، واحد، هدف، برنامه، اقدام، فعالیت، وضعیت تکمیل)
- امنیت: کاربران غیر ادمین فقط کاربرگ‌های واحد خودشان را می‌بینند

---

### 2. کلاس Export گزارشات
**مسیر:** `app/Exports/ReportsExport.php`

**ویژگی‌ها:**
- export تمام کاربرگ‌ها بر اساس فیلترهای انتخاب شده
- فرمت خروجی Excel با استایل و قالب‌بندی حرفه‌ای
- شامل اطلاعات ثابت (واحد، هدف، برنامه، اقدام، فعالیت) و فیلدهای متغیر
- استایل‌دهی هدر با رنگ سبز و border
- تنظیم خودکار عرض ستون‌ها

**ستون‌های خروجی:**
1. کد کاربرگ
2. واحد
3. کد هدف
4. عنوان هدف
5. کد برنامه
6. عنوان برنامه
7. کد اقدام
8. عنوان اقدام
9. عنوان فعالیت
10. وضعیت (تکمیل شده / در انتظار تکمیل)
11. تاریخ ایجاد
12. ایجاد کننده
13. فیلدهای متغیر و مقادیر

---

### 3. مستندات API
**مسیر:** `REPORTS_API_DOCUMENTATION.md`

**محتویات:**
- توضیحات کامل هر endpoint
- پارامترهای ورودی و خروجی
- نمونه درخواست‌ها و پاسخ‌ها (JSON)
- نمونه کدهای JavaScript برای استفاده در فرانت
- نمونه دستورات cURL برای تست
- نکات امنیتی و دسترسی

---

## فایل‌های تغییر یافته

### 1. Routes
**مسیر:** `routes/api.php`

**تغییرات:**
- اضافه شدن `use App\Http\Controllers\Api\ReportController;`
- اضافه شدن مسیرهای گزارشات:
  ```php
  Route::prefix('reports')->group(function () {
      Route::get('statistics', [ReportController::class, 'statistics']);
      Route::get('list', [ReportController::class, 'list']);
      Route::get('details/{id}', [ReportController::class, 'details']);
      Route::get('export', [ReportController::class, 'export']);
  });
  ```

---

### 2. مدل FormFieldValue
**مسیر:** `app/Models/FormFieldValue.php`

**تغییرات:**
- اضافه شدن متد `creator()` به عنوان alias برای `createdBy()`
- این تغییر برای سهولت دسترسی در ReportController است

---

## مسیرهای API جدید

تمام مسیرها تحت prefix `/api/reports` هستند و نیاز به authentication دارند:

| متد | مسیر | توضیحات |
|-----|------|---------|
| GET | `/api/reports/statistics` | دریافت آمار کلی |
| GET | `/api/reports/list` | لیست کاربرگ‌ها با فیلتر |
| GET | `/api/reports/details/{id}` | جزئیات یک کاربرگ |
| GET | `/api/reports/export` | دانلود گزارش Excel |

---

## فیلترهای موجود

### فیلترهای مشترک (در statistics، list، export):

| فیلتر | نوع | توضیحات |
|-------|-----|---------|
| `start_date` | date | تاریخ شروع (YYYY-MM-DD) |
| `end_date` | date | تاریخ پایان (YYYY-MM-DD) |
| `unit_id` | integer | شناسه واحد (فقط ادمین) |
| `target_id` | integer | شناسه هدف |
| `program_id` | integer | شناسه برنامه |
| `task_id` | integer | شناسه اقدام (فقط در list) |
| `activity_id` | integer | شناسه فعالیت (فقط در list) |
| `is_completed` | boolean | وضعیت تکمیل |
| `search` | string | جستجو در کد و توضیحات (فقط در list) |
| `per_page` | integer | تعداد در صفحه (فقط در list، پیش‌فرض: 10) |

---

## امنیت و دسترسی

### کاربران غیر ادمین:
- فقط کاربرگ‌های واحد خودشان را می‌بینند
- نمی‌توانند فیلتر `unit_id` را استفاده کنند

### کاربران ادمین (ADMIN):
- تمام کاربرگ‌ها را می‌بینند
- می‌توانند بر اساس واحد فیلتر کنند
- دسترسی کامل به تمام آمار

---

## نحوه استفاده در فرانت‌اند

### 1. دریافت آمار کلی

```javascript
const response = await axios.get('/api/reports/statistics', {
  params: {
    start_date: '2026-01-01',
    end_date: '2026-12-31',
    target_id: 1
  },
  headers: {
    Authorization: `Bearer ${token}`
  }
});

console.log(response.data.data.summary);
// {
//   total_forms: 150,
//   completed_forms: 120,
//   incomplete_forms: 30,
//   completion_percentage: 80.00
// }
```

### 2. دریافت لیست با صفحه‌بندی

```javascript
const response = await axios.get('/api/reports/list', {
  params: {
    per_page: 20,
    page: 1,
    is_completed: true,
    start_date: '2026-01-01'
  },
  headers: {
    Authorization: `Bearer ${token}`
  }
});

console.log(response.data.data); // آرایه کاربرگ‌ها
console.log(response.data.pagination); // اطلاعات صفحه‌بندی
```

### 3. دریافت جزئیات یک کاربرگ

```javascript
const formId = 1;
const response = await axios.get(`/api/reports/details/${formId}`, {
  headers: {
    Authorization: `Bearer ${token}`
  }
});

console.log(response.data.data.form); // اطلاعات فرم
console.log(response.data.data.fields); // فیلدها و مقادیر
```

### 4. دانلود گزارش Excel

```javascript
const response = await axios.get('/api/reports/export', {
  params: {
    start_date: '2026-01-01',
    end_date: '2026-12-31',
    is_completed: true
  },
  responseType: 'blob',
  headers: {
    Authorization: `Bearer ${token}`
  }
});

// دانلود فایل
const url = window.URL.createObjectURL(new Blob([response.data]));
const link = document.createElement('a');
link.href = url;
link.setAttribute('download', `report_${Date.now()}.xlsx`);
document.body.appendChild(link);
link.click();
link.remove();
```

---

## تست کردن

### با cURL:

```bash
# دریافت آمار
curl -X GET "http://localhost/api/reports/statistics" \
  -H "Authorization: Bearer YOUR_TOKEN"

# دریافت لیست
curl -X GET "http://localhost/api/reports/list?per_page=10&page=1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# دریافت جزئیات
curl -X GET "http://localhost/api/reports/details/1" \
  -H "Authorization: Bearer YOUR_TOKEN"

# دانلود گزارش
curl -X GET "http://localhost/api/reports/export" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output report.xlsx
```

### با Postman:

1. ایجاد collection جدید با نام "Reports"
2. اضافه کردن Authorization header با Bearer Token
3. ایجاد 4 request برای هر endpoint
4. تست با داده‌های مختلف

---

## نکات مهم

### 1. عملکرد
- استفاده از Eager Loading (`with()`) برای کاهش تعداد query
- استفاده از `clone` برای جلوگیری از تداخل queryها
- پیاده‌سازی صفحه‌بندی برای لیست‌های بزرگ

### 2. امنیت
- بررسی دسترسی در تمام متدها
- فیلتر خودکار بر اساس واحد کاربر
- validation ورودی‌ها

### 3. مستندسازی
- تمام APIها مستند شده‌اند
- نمونه کدها و پاسخ‌ها ارائه شده
- راهنمای استفاده در فرانت موجود است

---

## کارهای آینده (اختیاری)

1. **گزارش‌های پیشرفته‌تر:**
   - گزارش بر اساس بازه زمانی (روزانه، ماهانه، سالانه)
   - نمودارهای آماری (Chart.js)
   - مقایسه آماری بین واحدها

2. **Export به فرمت‌های دیگر:**
   - PDF
   - CSV

3. **گزارش‌های زمان‌بندی شده:**
   - ارسال گزارش خودکار به ایمیل
   - گزارش‌های دوره‌ای (هفتگی، ماهانه)

4. **داشبورد:**
   - داشبورد تصویری با نمودارها
   - ویجت‌های آماری

---

## پشتیبانی

در صورت بروز مشکل یا سوال، به مستندات API مراجعه کنید یا با تیم توسعه تماس بگیرید.
