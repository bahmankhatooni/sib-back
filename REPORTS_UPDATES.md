# به‌روزرسانی‌های صفحه گزارشات و کنترل دسترسی

## تاریخ: 2026-06-09

---

## خلاصه تغییرات

### 1. حذف فیلترهای تاریخ
- فیلدهای "از تاریخ" و "تا تاریخ" از صفحه گزارشات حذف شدند
- فیلترهای `start_date` و `end_date` از بک‌اند حذف شدند

### 2. اصلاح کنترل دسترسی
- تمام متدهای `FormController` با بررسی دسترسی به‌روزرسانی شدند
- کاربران غیر ادمین فقط به کاربرگ‌های واحد خود دسترسی دارند
- ادمین‌ها به تمام کاربرگ‌ها دسترسی دارند

---

## فایل‌های اصلاح شده

### Backend (sib-back)

#### 1. ReportController.php
**متدهای اصلاح شده:**
- `statistics()` - حذف فیلترهای تاریخ
- `list()` - حذف فیلترهای تاریخ
- `export()` - حذف فیلترهای تاریخ

**تغییرات:**
```php
// قبل:
if ($request->has('start_date') && !empty($request->start_date)) {
    $formsQuery->whereDate('created_at', '>=', $request->start_date);
}

// بعد:
// حذف شد
```

#### 2. ReportsExport.php
**تغییرات:**
- حذف فیلترهای `start_date` و `end_date` از collection

#### 3. FormController.php
**متدهای اصلاح شده:**

##### `index()` - لیست کاربرگ‌ها
```php
// اضافه شد:
$user = auth()->user();
$isAdmin = $user->role->slug === 'ADMIN';

if (!$isAdmin) {
    $query->where('unit_id', $user->unit_id);
}
```

##### `show($id)` - نمایش یک کاربرگ
```php
// اضافه شد:
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

##### `update($id)` - ویرایش کاربرگ
```php
// اضافه شد:
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

##### `destroy($id)` - حذف کاربرگ
```php
// اضافه شد:
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

##### `getFields($id)` - دریافت فیلدها
```php
// اضافه شد:
$form = Form::find($id);
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

##### `getForm($id)` - دریافت فرم
```php
// اضافه شد:
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

##### `saveFields($id)` - ذخیره فیلدها
```php
// اضافه شد:
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

##### `saveForm($id)` - ذخیره فرم
```php
// اضافه شد:
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

##### `export($id)` - دانلود Excel
```php
// اضافه شد:
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

---

### Frontend (sib-front)

#### 1. ReportsPage.vue

**تغییرات Template:**
```vue
// حذف شد:
<div class="form-group">
  <label>از تاریخ</label>
  <q-input v-model="filters.start_date" />
</div>
<div class="form-group">
  <label>تا تاریخ</label>
  <q-input v-model="filters.end_date" />
</div>
```

**تغییرات Script:**
```javascript
// قبل:
const filters = ref({
  start_date: null,
  end_date: null,
  unit_id: null,
  // ...
})

// بعد:
const filters = ref({
  unit_id: null,
  target_id: null,
  program_id: null,
  is_completed: null,
  search: null,
})
```

**تغییرات Style:**
```scss
// قبل:
.filter-grid { 
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
}

// بعد:
.filter-grid { 
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
}
```

---

## منطق کنترل دسترسی

### قوانین دسترسی:

#### کاربر ADMIN:
- ✅ مشاهده تمام کاربرگ‌ها (همه واحدها)
- ✅ ویرایش تمام کاربرگ‌ها
- ✅ حذف تمام کاربرگ‌ها
- ✅ دانلود Excel تمام کاربرگ‌ها
- ✅ فیلتر بر اساس واحد

#### کاربر UNIT_RECORDER (کاربر عادی):
- ✅ مشاهده فقط کاربرگ‌های واحد خودش
- ✅ ویرایش فقط کاربرگ‌های واحد خودش
- ✅ حذف فقط کاربرگ‌های واحد خودش
- ✅ دانلود Excel فقط کاربرگ‌های واحد خودش
- ❌ فیلتر واحد نمایش داده نمی‌شود

---

## کد بررسی دسترسی (الگو)

```php
// در ابتدای هر متد
$user = auth()->user();
$isAdmin = $user->role->slug === 'ADMIN';

// برای لیست‌ها (index, list)
if (!$isAdmin) {
    $query->where('unit_id', $user->unit_id);
}

// برای عملیات روی یک آیتم (show, update, destroy, etc.)
if (!$isAdmin && $form->unit_id !== $user->unit_id) {
    return response()->json([
        'success' => false,
        'message' => 'شما به این کاربرگ دسترسی ندارید'
    ], 403);
}
```

---

## نحوه تست

### 1. تست با کاربر Admin:

```bash
# لاگین با admin
POST /api/login
{
  "username": "admin",
  "password": "password"
}

# مشاهده تمام کاربرگ‌ها
GET /api/forms

# مشاهده گزارشات همه واحدها
GET /api/reports/list

# فیلتر بر اساس واحد خاص
GET /api/reports/list?unit_id=1
```

### 2. تست با کاربر عادی:

```bash
# لاگین با کاربر عادی (مثلاً واحد 2)
POST /api/login
{
  "username": "user1",
  "password": "password"
}

# مشاهده فقط کاربرگ‌های واحد 2
GET /api/forms

# مشاهده گزارشات فقط واحد خودش
GET /api/reports/list

# تلاش برای دسترسی به کاربرگ واحد دیگر (باید 403 بدهد)
GET /api/forms/1  # اگر کاربرگ 1 متعلق به واحد دیگری باشد
```

---

## پیام‌های خطا

### 403 Forbidden
```json
{
  "success": false,
  "message": "شما به این کاربرگ دسترسی ندارید"
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "کاربرگ مورد نظر یافت نشد"
}
```

---

## نکات مهم

1. **امنیت**: تمام متدهای CRUD حالا با بررسی دسترسی محافظت شده‌اند
2. **سازگاری**: منطق یکسان در تمام متدها پیاده‌سازی شده
3. **عملکرد**: بررسی دسترسی در سطح Query انجام می‌شود (کارآمدتر)
4. **تجربه کاربری**: پیام‌های خطای واضح و فارسی

---

## چک‌لیست تست

- [ ] Admin می‌تواند تمام کاربرگ‌ها را ببیند
- [ ] کاربر عادی فقط کاربرگ‌های واحد خودش را می‌بیند
- [ ] کاربر عادی نمی‌تواند کاربرگ واحد دیگر را ویرایش کند
- [ ] کاربر عادی نمی‌تواند کاربرگ واحد دیگر را حذف کند
- [ ] کاربر عادی نمی‌تواند کاربرگ واحد دیگر را دانلود کند
- [ ] Admin می‌تواند با فیلتر واحد جستجو کند
- [ ] کاربر عادی فیلتر واحد را نمی‌بیند
- [ ] فیلترهای تاریخ حذف شده‌اند
- [ ] گزارشات به درستی نمایش داده می‌شوند

---

## اقدامات بعدی (اختیاری)

- [ ] اضافه کردن لاگ برای تلاش‌های دسترسی غیرمجاز
- [ ] اضافه کردن Rate Limiting برای APIها
- [ ] پیاده‌سازی caching برای گزارشات
- [ ] اضافه کردن فیلترهای پیشرفته‌تر (اقدام، فعالیت)

---

تمام تغییرات با موفقیت اعمال شدند! ✅
