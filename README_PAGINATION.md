# ระบบ Pagination สำหรับ Media Drive

## ฟีเจอร์ที่เพิ่มเข้าไป

### 1. การแบ่งหน้า (Pagination)
- **แสดงผลแบบ Grid**: 12, 24, 48, 96 รายการต่อหน้า
- **Navigation**: ปุ่มไปหน้าแรก, ก่อนหน้า, ถัดไป, สุดท้าย
- **ข้อมูลสถิติ**: แสดงจำนวนรายการปัจจุบันและทั้งหมด
- **Responsive Design**: ใช้งานได้บนมือถือและเดสก์ท็อป

### 2. ประสิทธิภาพ (Performance)
- **Query Optimization**: ใช้ LIMIT และ OFFSET
- **Count Query**: นับจำนวนทั้งหมดแยกจาก main query
- **Parameter Binding**: ป้องกัน SQL Injection

### 3. UX/UI ที่ปรับปรุง
- **Loading Animation**: แสดงสถานะโหลดขณะเปลี่ยนหน้า
- **Smooth Transitions**: เอฟเฟกต์การเปลี่ยนหน้าที่ลื่นไหล
- **Keyboard Navigation**: ใช้ลูกศรซ้าย/ขวาเปลี่ยนหน้า
- **Hover Effects**: ปุ่มมีการตอบสนองเมื่อชี้เมาส์

### 4. การจัดการ State
- **URL Parameters**: เก็บสถานะ page, per_page, search, dept, subject
- **Session Persistence**: จำการตั้งค่าของผู้ใช้
- **Error Handling**: จัดการกรณีหน้าไม่มีข้อมูล

## ไฟล์ที่เกี่ยวข้อง

### 1. `dashboard.php` (แก้ไข)
- เพิ่มระบบ pagination parameters
- แก้ไข SQL queries ให้รองรับ LIMIT/OFFSET
- เพิ่มการนับจำนวนรายการทั้งหมด

### 2. `pagination.php` (ไฟล์ใหม่)
- Component สำหรับแสดง pagination UI
- JavaScript สำหรับ UX interactions
- ฟังก์ชันสร้าง URL parameters

### 3. `media_content.php` (แก้ไข)
- เพิ่มการ include pagination component
- ปรับปรุงการแสดงผลรายการสื่อ

### 4. `styles.php` (แก้ไข)
- เพิ่ม CSS สำหรับ pagination
- Responsive design rules
- Animation effects

## การใช้งาน

### 1. URL Parameters
```
?page=2&per_page=24&search=คำค้นหา&dept=1&subject=5
```

### 2. Keyboard Shortcuts
- **← (ซ้าย)**: หน้าก่อนหน้า
- **→ (ขวา)**: หน้าถัดไป

### 3. การตั้งค่า
```php
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 12;
$allowed_per_page = [12, 24, 48, 96];
```

## ข้อดีของระบบนี้

### 1. **Performance**
- ลดการโหลดข้อมูลที่ไม่จำเป็น
- Query เร็วขึ้นสำหรับฐานข้อมูลขนาดใหญ่
- Bandwidth ที่ใช้น้อยลง

### 2. **User Experience**
- โหลดหน้าเร็วขึ้น
- Navigation ง่ายและสะดวก
- Visual feedback ชัดเจน

### 3. **Scalability**
- รองรับข้อมูลหลายพันรายการ
- Memory usage ที่เหมาะสม
- Database load ที่สมดุล

### 4. **Maintainability**
- แยก component ออกจากกัน
- Code ที่อ่านง่าย
- ขยายฟีเจอร์ได้ง่าย

## การ Customize

### 1. เปลี่ยนจำนวนรายการต่อหน้า
```php
$allowed_per_page = [6, 12, 24, 48]; // แก้ไขใน dashboard.php
```

### 2. เปลี่ยนจำนวนปุ่มที่แสดง
```php
$start_page = max(1, $page - 3); // แสดง 7 ปุ่ม แทน 5
$end_page = min($total_pages, $page + 3);
```

### 3. เพิ่ม Animation Effects
```css
.media-card:nth-child(7) { animation-delay: 0.7s; }
.media-card:nth-child(8) { animation-delay: 0.8s; }
```

## Libraries ที่ใช้

### 1. **CSS Framework**
- **Tailwind CSS**: สำหรับ responsive design
- **Font Awesome**: ไอคอน

### 2. **JavaScript**
- **Vanilla JS**: ไม่ต้องพึ่ง external library
- **CSS Transitions**: สำหรับ animations

### 3. **PHP Features**
- **PDO/MySQLi**: Database operations
- **Prepared Statements**: Security

## Best Practices

### 1. **Security**
- ใช้ prepared statements
- Validate input parameters
- Escape output ด้วย htmlspecialchars()

### 2. **Performance**
- Index database columns ที่ใช้ใน WHERE, ORDER BY
- Cache query results ถ้าจำเป็น
- Optimize images (thumbnails)

### 3. **Accessibility**
- Keyboard navigation support
- Screen reader friendly
- High contrast design

## การ Debug

### 1. ตรวจสอบ SQL Query
```php
echo $media_query; // แสดง query ที่สร้าง
var_dump($params); // แสดง parameters
```

### 2. ตรวจสอบ Pagination Variables
```php
echo "Page: $page, Per Page: $per_page, Total: $total_records";
```

### 3. Browser Console
```javascript
console.log('Current page:', <?php echo $page; ?>);
console.log('Total pages:', <?php echo $total_pages; ?>);
```

ระบบ pagination นี้ออกแบบมาให้มีประสิทธิภาพสูง, ใช้งานง่าย, และขยายได้ในอนาคต! 🚀
