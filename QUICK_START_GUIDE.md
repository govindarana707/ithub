# 🚀 eSewa Integration - Quick Start Guide

## ⚡ Quick Testing (5 Minutes)

### Step 1: Set Up Test Session
```
Go to: http://localhost/store/setup_test_session.php
```
This creates a logged-in session as student1 (ID: 3)

### Step 2: Test Payment
```
Go to: http://localhost/store/test_final_esewa.php
Click: "🟢 Pay with eSewa (Test)"
```

### Step 3: Complete Payment
- **eSewa ID**: 9806800001
- **Password**: Nepal@123  
- **Token**: 123456

### Step 4: Verify Enrollment
```
Go to: http://localhost/store/courses.php
Check: You should be enrolled in the course
```

---

## 🔧 Production Deployment

### Update Settings:
```sql
UPDATE payment_settings SET setting_value = 'false' WHERE setting_key = 'esewa_test_mode';
UPDATE payment_settings SET setting_value = 'YOUR_PRODUCTION_KEY' WHERE setting_key = 'esewa_secret_key';
UPDATE payment_settings SET setting_value = 'YOUR_PRODUCT_CODE' WHERE setting_key = 'esewa_product_code';
```

### Files to Deploy:
- `services/SignatureService.php`
- `services/PaymentService.php` 
- `services/EnrollmentServiceNew.php`
- `api/esewa_payment.php`
- `api/esewa_success.php`
- `api/esewa_failure.php`
- Database schema from `database/esewa_payment_schema.sql`

---

## 🐛 Common Issues & Fixes

### "Invalid payload signature"
→ Amount formatting issue - our code handles this automatically

### "Foreign key constraint fails" 
→ User doesn't exist - use `setup_test_session.php`

### "Payment already exists"
→ Clear test payments: `clear_payments.php`

### Session issues
→ Always run `setup_test_session.php` first

---

## 📞 Need Help?

1. Check `ESEWA_INTEGRATION_COMPLETE.md` for full documentation
2. Use debug files for troubleshooting
3. Check payment logs in database

**Ready to go! 🎯**
