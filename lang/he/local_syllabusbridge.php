<?php
defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'גשר לאפליקציית סילבוס';
$string['navlaunch'] = 'אפליקציה לניהול סילבוס';
$string['appurl'] = 'כתובת בסיס של האפליקציה';
$string['appurl_desc'] = 'כתובת מלאה לתיקיית public של האפליקציה, בלי סלאש בסוף (למשל https://example.org/standalone/public).';
$string['sharedsecret'] = 'סוד משותף (HMAC)';
$string['sharedsecret_desc'] = 'חייב להיות זהה ל-MOODLE_BRIDGE_SECRET בקובץ config.php של האפליקציה.';
$string['errorconfig'] = 'כתובת האפליקציה או הסוד המשותף לא הוגדרו. פנה למנהל המערכת.';
$string['erroremail'] = 'חובה שיהיה כתובת דוא״ל תקינה בחשבון המודל כדי להשתמש באפליקציית הסילבוס.';
$string['cannotusebridge'] = 'אין לך הרשאה לפתוח את אפליקציית הסילבוס בקורס זה (נדרשת גישת מורה או עורך קורס).';
$string['syncuserid'] = 'מזהה משתמש ליצירת פעילויות בקורס';
$string['syncuserid_desc'] = 'מזהה משתמש במודל שבשמו נוצרת/מתעדכנת פעילות הקישור (חייב הרשאת ניהול פעילויות בקורס היעד). 0 = משתמש מנהל ראשון שנמצא.';
$string['urlsection'] = 'מספר יחידה לשיבוץ קישור הסילבוס';
$string['urlsection_desc'] = '-1 = אוטומטי (יחידה ששמה מכיל «מבוא» או intro), 0 = אזור כללי למעלה, 1 = יחידה ראשונה, וכו׳.';
