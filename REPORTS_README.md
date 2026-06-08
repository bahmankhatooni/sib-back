# بخش گزارشات (Reports Module)

## نصب و راه‌اندازی

بخش گزارشات به صورت کامل پیاده‌سازی شده و آماده استفاده است. نیازی به نصب package اضافی نیست.

### فایل‌های اضافه شده:

✅ `app/Http/Controllers/Api/ReportController.php` - کنترلر گزارشات  
✅ `app/Exports/ReportsExport.php` - کلاس export به Excel  
✅ `routes/api.php` - مسیرهای API اضافه شده  
✅ `app/Models/FormFieldValue.php` - اصلاح شده (اضافه شدن متد creator)  

### مستندات:

📄 `REPORTS_API_DOCUMENTATION.md` - راهنمای کامل API  
📄 `REPORTS_CHANGELOG.md` - لیست تغییرات و توضیحات تکمیلی  
📄 `REPORTS_README.md` - این فایل  

---

## API Endpoints

### Base URL: `/api/reports`

| Endpoint | Method | توضیحات |
|----------|--------|---------|
| `/statistics` | GET | آمار کلی سیستم |
| `/list` | GET | لیست کاربرگ‌ها با فیلتر |
| `/details/{id}` | GET | جزئیات یک کاربرگ |
| `/export` | GET | دانلود Excel |

---

## نمونه استفاده سریع

### 1. دریافت آمار:

```bash
GET /api/reports/statistics?start_date=2026-01-01&end_date=2026-12-31
```

**پاسخ:**
```json
{
  "success": true,
  "data": {
    "summary": {
      "total_forms": 150,
      "completed_forms": 120,
      "incomplete_forms": 30,
      "completion_percentage": 80.00
    }
  }
}
```

### 2. دریافت لیست:

```bash
GET /api/reports/list?per_page=20&is_completed=true
```

### 3. دانلود Excel:

```bash
GET /api/reports/export?start_date=2026-01-01&is_completed=true
```

---

## فیلترهای موجود

✔️ تاریخ شروع و پایان (`start_date`, `end_date`)  
✔️ واحد (`unit_id` - فقط ادمین)  
✔️ هدف (`target_id`)  
✔️ برنامه (`program_id`)  
✔️ اقدام (`task_id`)  
✔️ فعالیت (`activity_id`)  
✔️ وضعیت تکمیل (`is_completed`)  
✔️ جستجو (`search`)  

---

## دسترسی‌ها

### کاربر عادی (UNIT_RECORDER):
- دسترسی فقط به کاربرگ‌های واحد خودش
- امکان فیلتر بر اساس تاریخ، هدف، برنامه
- امکان export کاربرگ‌های واحد خودش

### ادمین (ADMIN):
- دسترسی به تمام کاربرگ‌ها
- امکان فیلتر بر اساس واحد
- امکان export تمام کاربرگ‌ها

---

## نحوه استفاده در فرانت

برای مثال‌های کامل به فایل `REPORTS_API_DOCUMENTATION.md` مراجعه کنید.

### ساده‌ترین نمونه (JavaScript):

```javascript
// دریافت آمار
const getStats = async () => {
  const response = await fetch('/api/reports/statistics', {
    headers: {
      'Authorization': `Bearer ${yourToken}`,
      'Accept': 'application/json'
    }
  });
  return await response.json();
};

// استفاده
const stats = await getStats();
console.log(stats.data.summary.total_forms);
```

---

## تست سریع با cURL

```bash
# 1. لاگین و دریافت token
TOKEN=$(curl -X POST http://localhost/api/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"password"}' \
  | jq -r '.token')

# 2. دریافت آمار
curl -X GET "http://localhost/api/reports/statistics" \
  -H "Authorization: Bearer $TOKEN"

# 3. دانلود گزارش
curl -X GET "http://localhost/api/reports/export" \
  -H "Authorization: Bearer $TOKEN" \
  --output report.xlsx
```

---

## ویژگی‌های کلیدی

🚀 **سریع و بهینه** - استفاده از Eager Loading و Query Optimization  
🔒 **امن** - کنترل دسترسی بر اساس نقش کاربر  
📊 **آماری** - محاسبه خودکار آمار بر اساس واحد، هدف و برنامه  
📁 **Export قدرتمند** - خروجی Excel با فرمت حرفه‌ای  
🔍 **فیلترهای پیشرفته** - امکان فیلتر بر اساس پارامترهای مختلف  
📄 **مستند** - مستندسازی کامل با نمونه کد  

---

## Troubleshooting

### مشکل: خطای 401 Unauthorized
**راه‌حل:** مطمئن شوید که token را در header ارسال می‌کنید:
```
Authorization: Bearer YOUR_TOKEN
```

### مشکل: فایل Excel دانلود نمی‌شود
**راه‌حل:** 
1. بررسی کنید که package `maatwebsite/excel` نصب است
2. مطمئن شوید `responseType: 'blob'` در Axios تنظیم شده

### مشکل: آمار نادرست است
**راه‌حل:** 
1. بررسی کنید فیلترهای تاریخ صحیح هستند
2. مطمئن شوید کاربر دسترسی لازم را دارد

---

## مستندات بیشتر

- [راهنمای کامل API](./REPORTS_API_DOCUMENTATION.md)
- [لیست تغییرات و جزئیات](./REPORTS_CHANGELOG.md)

---

## نتیجه‌گیری

بخش گزارشات به صورت کامل پیاده‌سازی شده و آماده استفاده است. 

✅ Backend کامل شده  
✅ APIها تست شده  
✅ مستندات آماده  
⏳ Frontend (نیاز به پیاده‌سازی در sib-front)

برای پیاده‌سازی صفحه گزارشات در فرانت، از API endpoints معرفی شده استفاده کنید.
