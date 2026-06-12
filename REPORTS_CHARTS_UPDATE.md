# به‌روزرسانی نمودارهای گزارشات

## تاریخ: 2026-06-09

---

## خلاصه تغییرات

### ✅ مشکلات حل شده:

#### 1. نمایش تمام اهداف در نمودار دونات
**قبل:**
- فقط 5 هدف اول نمایش داده می‌شد
- slice(0, 5) استفاده می‌شد

**بعد:**
- تمام اهداف از جدول targets نمایش داده می‌شوند
- لیست scrollable برای تعداد زیاد اهداف
- رنگ‌های متنوع برای تمام اهداف

#### 2. نمایش نمودار میله‌ای واحدها
**قبل:**
- نمودار خالی بود
- برای کاربران عادی معنایی نداشت

**بعد:**
- فقط برای ادمین نمایش داده می‌شود
- فقط واحدهایی که کاربرگ دارند نمایش داده می‌شوند
- برای کاربران عادی نمودار دونات عرض کامل دارد

---

## تغییرات تکنیکال

### Backend (sib-back)

#### 1. ReportController.php

**متد `statistics()` - بخش `by_unit`:**

```php
// قبل:
$statsByUnit = Unit::select('units.id', 'units.name', DB::raw('COUNT(forms.id) as total_forms'))
    ->leftJoin('forms', function($join) use ($request, $isAdmin, $user) {
        $join->on('units.id', '=', 'forms.unit_id');
        if (!$isAdmin) {
            $join->where('forms.unit_id', '=', $user->unit_id);
        }
    })
    ->groupBy('units.id', 'units.name')
    ->get();

// بعد:
$statsByUnitQuery = Unit::select('units.id', 'units.name', DB::raw('COUNT(forms.id) as total_forms'))
    ->leftJoin('forms', 'units.id', '=', 'forms.unit_id');

// برای کاربران غیر ادمین، فقط واحد خودشان را نمایش می‌دهیم
if (!$isAdmin) {
    $statsByUnitQuery->where('units.id', '=', $user->unit_id);
}

$statsByUnit = $statsByUnitQuery
    ->groupBy('units.id', 'units.name')
    ->get();
```

**دلیل تغییر:**
- Query قبلی در join شرط می‌گذاشت که باعث می‌شد داده نادرست برگردد
- Query جدید ابتدا join می‌کند و سپس filter می‌کند

---

### Frontend (sib-front)

#### 1. ReportsPage.vue - Template

**نمودارها:**

```vue
<!-- قبل: -->
<div class="charts-row" v-if="statistics">
  <div class="chart-card chart-card--wide">
    <!-- نمودار میله‌ای همیشه نمایش داده می‌شد -->
  </div>
  <div class="chart-card chart-card--narrow">
    <!-- نمودار دونات -->
  </div>
</div>

<!-- بعد: -->
<div class="charts-row" v-if="statistics">
  <!-- نمودار میله‌ای فقط برای ادمین -->
  <div class="chart-card chart-card--wide" v-if="isAdmin && statistics.by_unit.length > 0">
    <!-- نمودار میله‌ای -->
  </div>
  
  <!-- نمودار دونات - اگر ادمین نیست، عرض کامل -->
  <div 
    class="chart-card" 
    :class="isAdmin ? 'chart-card--narrow' : 'chart-card--full'"
  >
    <!-- نمودار دونات -->
  </div>
</div>
```

**لیست اهداف زیر نمودار:**

```vue
<!-- قبل: -->
<div class="donut-legend-item" v-for="target in statistics.by_target.slice(0, 5)" :key="target.id">

<!-- بعد: -->
<div class="donut-legend-item" v-for="(target, index) in statistics.by_target" :key="target.id">
```

#### 2. ReportsPage.vue - Script

**تابع `updateCharts()` - نمودار میله‌ای:**

```javascript
// قبل:
if (barChart.value && statistics.value.by_unit.length > 0) {
  // ایجاد نمودار برای همه
}

// بعد:
if (barChart.value && isAdmin.value && statistics.value.by_unit.length > 0) {
  // فیلتر واحدهایی که حداقل یک کاربرگ دارند
  const unitsWithForms = statistics.value.by_unit.filter(unit => unit.total_forms > 0)
  
  if (unitsWithForms.length > 0) {
    // ایجاد نمودار فقط برای ادمین
  }
}
```

**تابع `updateCharts()` - نمودار دونات:**

```javascript
// قبل:
const topTargets = statistics.value.by_target.slice(0, 5)
const colors = topTargets.map((_, index) => getTargetColor(index))

// بعد:
const allTargets = statistics.value.by_target
const colors = []
const baseColors = ['#1e8a5e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#a855f7']

for (let i = 0; i < allTargets.length; i++) {
  colors.push(baseColors[i % baseColors.length])
}
```

**تابع `getTargetColor()`:**

```javascript
// قبل:
const getTargetColor = (targetId) => {
  const colors = ['#1e8a5e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6']
  return colors[targetId % colors.length]
}

// بعد:
const getTargetColor = (index) => {
  const colors = ['#1e8a5e', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#14b8a6', '#f97316', '#a855f7']
  return colors[index % colors.length]
}
```

#### 3. ReportsPage.vue - Styles

**افزودن scrollbar برای لیست اهداف:**

```scss
.donut-legend { 
  display: flex; 
  flex-direction: column; 
  gap: 7px; 
  max-height: 200px;          // محدودیت ارتفاع
  overflow-y: auto;            // scroll عمودی
  padding-right: 8px;          // فاصله برای scrollbar
}

// استایل scrollbar
.donut-legend::-webkit-scrollbar { width: 6px; }
.donut-legend::-webkit-scrollbar-track { background: #f1f5f9; border-radius: 3px; }
.donut-legend::-webkit-scrollbar-thumb { background: #94a3b8; border-radius: 3px; }
.donut-legend::-webkit-scrollbar-thumb:hover { background: #64748b; }
```

**افزودن کلاس full-width:**

```scss
.chart-card--full { 
  grid-column: 1 / -1;  // عرض کامل
}
```

**Responsive updates:**

```scss
@media (max-width: 1100px) {
  .charts-row { 
    grid-template-columns: 1fr; 
    .chart-card--narrow, .chart-card--full {
      grid-column: auto;
    }
  }
}
```

---

## ویژگی‌های جدید

### 1. نمودار دونات (Donut Chart)
- ✅ نمایش تمام اهداف (نه فقط 5 تا)
- ✅ رنگ‌های متنوع برای تمام اهداف (10 رنگ مختلف)
- ✅ لیست scrollable برای تعداد زیاد اهداف
- ✅ Tooltip با نمایش تعداد کاربرگ
- ✅ عرض کامل برای کاربران عادی

### 2. نمودار میله‌ای (Bar Chart)
- ✅ فقط برای ادمین نمایش داده می‌شود
- ✅ فقط واحدهایی که کاربرگ دارند نمایش داده می‌شوند
- ✅ Tooltip با نمایش تعداد کاربرگ
- ✅ stepSize: 1 برای محور Y (اعداد صحیح)

---

## رنگ‌های استفاده شده

```javascript
const colors = [
  '#1e8a5e', // سبز
  '#3b82f6', // آبی
  '#f59e0b', // نارنجی
  '#ef4444', // قرمز
  '#8b5cf6', // بنفش
  '#06b6d4', // فیروزه‌ای
  '#ec4899', // صورتی
  '#14b8a6', // سبز آبی
  '#f97316', // نارنجی تیره
  '#a855f7'  // بنفش روشن
]
```

---

## نمایش بر اساس نقش

### Admin:
```
┌─────────────────────────────┐  ┌──────────────┐
│ نمودار میله‌ای (واحدها)     │  │ نمودار دونات │
│ مقایسه تعداد کاربرگ‌ها       │  │ توزیع اهداف  │
│                             │  │              │
│ [نمودار bar chart]          │  │ [دونات]      │
│                             │  │              │
│                             │  │ - هدف 1: 5   │
│                             │  │ - هدف 2: 3   │
│                             │  │ - هدف 3: 2   │
└─────────────────────────────┘  └──────────────┘
```

### کاربر عادی:
```
┌─────────────────────────────────────────────┐
│          نمودار دونات (عرض کامل)             │
│          توزیع بر اساس اهداف                 │
│                                             │
│            [نمودار دونات]                    │
│                                             │
│ - هدف 1: 5 کاربرگ                          │
│ - هدف 2: 3 کاربرگ                          │
│ - هدف 3: 2 کاربرگ                          │
│ - هدف 4: 1 کاربرگ                          │
│ - ... (scrollable)                          │
└─────────────────────────────────────────────┘
```

---

## تست

### چک‌لیست:

#### Admin:
- [x] نمودار میله‌ای نمایش داده می‌شود
- [x] فقط واحدهایی که کاربرگ دارند در نمودار هستند
- [x] نمودار دونات تمام اهداف را نمایش می‌دهد
- [x] لیست اهداف scrollable است
- [x] Tooltip در هر دو نمودار کار می‌کند

#### کاربر عادی:
- [x] نمودار میله‌ای نمایش داده نمی‌شود
- [x] نمودار دونات عرض کامل دارد
- [x] تمام اهداف نمایش داده می‌شوند
- [x] لیست اهداف scrollable است

#### Responsive:
- [x] در موبایل نمودارها یک ستونی می‌شوند
- [x] scrollbar در موبایل کار می‌کند

---

## نکات مهم

1. **عملکرد**: فیلتر کردن واحدهایی که کاربرگ ندارند باعث بهبود خوانایی نمودار می‌شود
2. **UX**: نمایش نمودار واحدها فقط برای ادمین منطقی است
3. **رنگ‌بندی**: 10 رنگ مختلف برای تنوع بیشتر
4. **Scrollable**: لیست بلند اهداف قابل scroll است
5. **Responsive**: نمودارها در موبایل به خوبی نمایش داده می‌شوند

---

تمام تغییرات اعمال شد! ✅
