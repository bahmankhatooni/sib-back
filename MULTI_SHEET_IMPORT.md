# قابلیت Import چند شیتی کاربرگ‌ها

## خلاصه

سیستم حالا می‌تواند فایل‌های Excel با **چند شیت** را import کند و به تعداد شیت‌ها، کاربرگ ایجاد کند.

---

## تغییرات اصلی

### ❌ قبل:
- فقط **یک شیت** import می‌شد
- **نام فایل** مهم بود (باید مطابق کد کاربرگ می‌بود)
- فیلد `code` در request الزامی بود

### ✅ بعد:
- **چند شیت** import می‌شود
- **نام هر شیت** به عنوان کد کاربرگ استفاده می‌شود
- نام فایل مهم نیست
- فیلد `code` در request حذف شد

---

## نحوه استفاده

### 1. آماده‌سازی فایل Excel

```
📄 MyWorkbook.xlsx
├── 📑 شیت 1: "30201-4"
│   └── قالب استاندارد کاربرگ
├── 📑 شیت 2: "30201-5"
│   └── قالب استاندارد کاربرگ
├── 📑 شیت 3: "30202-1"
│   └── قالب استاندارد کاربرگ
└── 📑 شیت 4: "30202-2"
    └── قالب استاندارد کاربرگ
```

**نکات مهم:**
- نام هر شیت = کد کاربرگی که ایجاد می‌شود
- اگر نام شیت خالی باشد، از "Sheet_1", "Sheet_2", ... استفاده می‌شود
- قالب داخل هر شیت باید مطابق استاندارد باشد (6 ردیف اول + هدر و مقادیر)

---

### 2. قالب استاندارد هر شیت

```
ردیف 1 (A1): هدف 5 : عنوان هدف
ردیف 2 (A2): برنامه 4 : عنوان برنامه
ردیف 3 (A3): اقدام 50401 : عنوان اقدام
ردیف 4 (A4): فعالیت 1 : عنوان فعالیت
ردیف 5 (A5): ردیف
ردیف 6 (A6, B6, ...): عناوین فیلدهای متغیر
ردیف 7 (A7, B7, ...): مقادیر فیلدهای متغیر
```

---

## API

### Endpoint
```
POST /api/forms/import
```

### Request

**Headers:**
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Body (Form Data):**
```
file: [Excel File] (required)
```

**تغییر:** فیلد `code` دیگر لازم نیست! ❌

### Response موفق (200)

```json
{
  "success": true,
  "message": "4 کاربرگ با موفقیت ایجاد شد",
  "imported": [
    "30201-4",
    "30201-5",
    "30202-1",
    "30202-2"
  ],
  "imported_count": 4
}
```

### Response با خطا جزئی (200)

```json
{
  "success": true,
  "message": "2 کاربرگ با موفقیت ایجاد شد",
  "imported": [
    "30201-4",
    "30201-5"
  ],
  "errors": [
    "کاربرگ با کد '30202-1' قبلاً وجود دارد",
    "خطا در پردازش شیت '30202-2': هدف با کد '99' یافت نشد"
  ],
  "imported_count": 2,
  "error_count": 2
}
```

### Response با خطای کامل (422)

```json
{
  "success": false,
  "message": "",
  "imported": [],
  "errors": [
    "کاربرگ با کد '30201-4' قبلاً وجود دارد",
    "کاربرگ با کد '30201-5' قبلاً وجود دارد"
  ],
  "imported_count": 0,
  "error_count": 2
}
```

---

## مثال‌های استفاده

### مثال 1: Import با cURL

```bash
curl -X POST http://localhost:8000/api/forms/import \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@workbook.xlsx"
```

### مثال 2: Import با JavaScript (Axios)

```javascript
const formData = new FormData()
formData.append('file', fileInput.files[0])

const response = await axios.post('/api/forms/import', formData, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'multipart/form-data'
  }
})

console.log(`${response.data.imported_count} کاربرگ ایجاد شد`)
console.log('کاربرگ‌های ایجاد شده:', response.data.imported)

if (response.data.errors) {
  console.log('خطاها:', response.data.errors)
}
```

### مثال 3: Import در Vue/Quasar

```vue
<template>
  <q-file
    v-model="file"
    label="انتخاب فایل Excel"
    accept=".xlsx,.xls"
    @update:model-value="handleFileSelect"
  >
    <template v-slot:prepend>
      <q-icon name="attach_file" />
    </template>
  </q-file>
  
  <q-btn 
    label="آپلود و Import"
    color="primary"
    @click="importFile"
    :loading="uploading"
  />
</template>

<script setup>
import { ref } from 'vue'
import { api } from 'boot/axios'
import { useQuasar } from 'quasar'

const $q = useQuasar()
const file = ref(null)
const uploading = ref(false)

const importFile = async () => {
  if (!file.value) {
    $q.notify({ type: 'warning', message: 'لطفاً فایل را انتخاب کنید' })
    return
  }
  
  uploading.value = true
  
  try {
    const formData = new FormData()
    formData.append('file', file.value)
    
    const response = await api.post('/forms/import', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    
    if (response.data.success) {
      $q.notify({
        type: 'positive',
        message: response.data.message,
        caption: `${response.data.imported_count} کاربرگ ایجاد شد`,
        timeout: 3000
      })
      
      // نمایش لیست کاربرگ‌های ایجاد شده
      if (response.data.imported.length <= 5) {
        console.log('کاربرگ‌ها:', response.data.imported.join(', '))
      }
    }
    
    // نمایش خطاها (اگر وجود داشت)
    if (response.data.errors && response.data.errors.length > 0) {
      $q.notify({
        type: 'warning',
        message: 'برخی شیت‌ها با خطا مواجه شدند',
        caption: `${response.data.error_count} خطا`,
        timeout: 5000
      })
    }
    
    // پاک کردن فایل
    file.value = null
    
  } catch (error) {
    $q.notify({
      type: 'negative',
      message: 'خطا در آپلود فایل',
      caption: error.response?.data?.message || error.message
    })
  } finally {
    uploading.value = false
  }
}
</script>
```

---

## جزئیات فنی

### فایل‌های تغییر یافته:

#### 1. FormController.php

**متد `import()`:**
- حذف validation فیلد `code`
- اضافه شدن حلقه برای پردازش چند شیت
- استفاده از `PhpOffice\PhpSpreadsheet` برای خواندن شیت‌ها

**متد جدید `processSheet()`:**
- پردازش یک شیت و ایجاد کاربرگ
- خواندن 7 ردیف اول برای اطلاعات ثابت و متغیر
- ایجاد Form، FormField و FormFieldValue

#### 2. FormsImport.php

- ساده‌سازی شده
- فقط برای سازگاری با interface باقی مانده
- منطق اصلی به FormController منتقل شده

---

## منطق پردازش

```
1. کاربر فایل Excel را آپلود می‌کند
   ↓
2. سیستم تعداد شیت‌ها را شناسایی می‌کند
   ↓
3. برای هر شیت:
   ├─ نام شیت = کد کاربرگ
   ├─ بررسی تکراری نبودن کد
   ├─ خواندن ردیف‌های 1-7
   ├─ استخراج اطلاعات هدف، برنامه، اقدام، فعالیت
   ├─ یافتن ID های مربوطه در دیتابیس
   ├─ ایجاد Form
   ├─ ایجاد FormField ها
   └─ ایجاد FormFieldValue ها
   ↓
4. بازگشت نتیجه:
   ├─ لیست کاربرگ‌های موفق
   └─ لیست خطاها (اگر وجود داشت)
```

---

## خطاهای ممکن

### 1. کاربرگ تکراری
```
"کاربرگ با کد '30201-4' قبلاً وجود دارد"
```
**راه‌حل:** نام شیت را تغییر دهید یا کاربرگ قدیمی را حذف کنید

### 2. هدف یافت نشد
```
"هدف با کد '5' یافت نشد"
```
**راه‌حل:** مطمئن شوید هدف در سیستم ثبت شده است

### 3. برنامه یافت نشد
```
"برنامه با کد '4' یافت نشد"
```
**راه‌حل:** مطمئن شوید برنامه در زیرمجموعه هدف صحیح ثبت شده است

### 4. فرمت نادرست
```
"فرمت ردیف هدف نادرست است"
```
**راه‌حل:** مطمئن شوید فرمت ردیف‌ها مطابق استاندارد است

---

## مزایا

✅ **صرفه‌جویی در زمان:** ایجاد چندین کاربرگ با یک بار آپلود  
✅ **راحتی استفاده:** نیازی به تکرار فرآیند import نیست  
✅ **انعطاف‌پذیری:** نام فایل مهم نیست، فقط نام شیت‌ها  
✅ **مدیریت خطا:** خطاهای جزئی مانع ایجاد سایر کاربرگ‌ها نمی‌شود  
✅ **گزارش کامل:** اطلاع از تعداد موفق و ناموفق  

---

## محدودیت‌ها

⚠️ نام شیت باید **یکتا** باشد در سیستم  
⚠️ قالب هر شیت باید **مطابق استاندارد** باشد  
⚠️ هدف و برنامه باید **از قبل در سیستم** ثبت شده باشند  

---

## تست

### سناریو 1: Import موفق تمام شیت‌ها
```
فایل: 3 شیت با نام‌های یکتا
نتیجه: 3 کاربرگ ایجاد می‌شود
Status: 200
```

### سناریو 2: Import با یک شیت تکراری
```
فایل: 3 شیت، یکی تکراری
نتیجه: 2 کاربرگ ایجاد، 1 خطا
Status: 200 (چون 2 تا موفق بود)
```

### سناریو 3: Import با همه شیت‌های تکراری
```
فایل: 3 شیت، همه تکراری
نتیجه: 0 کاربرگ ایجاد، 3 خطا
Status: 422
```

### سناریو 4: Import با فرمت نادرست
```
فایل: 3 شیت، یکی فرمت نادرست
نتیجه: 2 کاربرگ ایجاد، 1 خطا
Status: 200
```

---

## نکات مهم برای توسعه‌دهندگان Frontend

1. **حذف فیلد code از فرم:**
```javascript
// قبل:
const formData = new FormData()
formData.append('file', file)
formData.append('code', code) // ❌ حذف شد

// بعد:
const formData = new FormData()
formData.append('file', file) // ✅ فقط فایل
```

2. **نمایش نتایج:**
```javascript
// بررسی تعداد موفق و ناموفق
if (response.data.imported_count > 0) {
  showSuccess(`${response.data.imported_count} کاربرگ ایجاد شد`)
}

if (response.data.error_count > 0) {
  showWarning(`${response.data.error_count} خطا رخ داد`)
}
```

3. **نمایش لیست کاربرگ‌ها:**
```javascript
// فقط اگر تعداد کم بود
if (response.data.imported.length <= 5) {
  console.log(response.data.imported.join(', '))
}
```

---

تمام! قابلیت Import چند شیتی آماده است! 🎉
