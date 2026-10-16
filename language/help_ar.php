<?php
//
// vbulletin_integration, its guide on the help page of the control panel
// Arabic, see help_en.php for how the words are named
//
// A word missing here is shown in English.
// Names in <strong> are written as the control panel shows them in Arabic,
// the words of Kleeja are in lang/ar/, the words of the plugin in vbulletin_integration_translations() of init.php.
// A <code> that begins or ends with a symbol takes dir="ltr", or the symbol shows on the wrong side.
//

return [
    'VBULLETIN_INTR_HELP_TITLE' => 'التكامل مع vBulletin',
    'VBULLETIN_INTR_HELP_INTRO' =>
        'يتيح لأعضاء منتدى vBulletin تسجيل الدخول إلى كليجا باسم المستخدم وكلمة المرور الخاصين بهم في المنتدى، بدلًا من إنشاء حسابات منفصلة في كليجا.',

    //
    // what the plugin does
    //
    'VBULLETIN_INTR_HELP_FEATURE_1' =>
        'يسجّل الأعضاء دخولهم بحساباتهم في المنتدى. تقرأ كليجا قاعدة بيانات المنتدى فقط، ولا تغيّر فيها شيئًا.',
    'VBULLETIN_INTR_HELP_FEATURE_2' =>
        'ينضم مديرو المنتدى إلى مجموعة المديرين في كليجا ويمكنهم دخول لوحة التحكم، وينضم باقي الأعضاء إلى مجموعة الأعضاء، ولا يستطيع الأعضاء المحظورون تسجيل الدخول.',
    'VBULLETIN_INTR_HELP_FEATURE_3' =>
        'توجّه صفحة <strong>تسجيل عضوية</strong> واستعادة كلمة المرور وتعديل الملف الشخصي الأعضاءَ إلى الصفحات المقابلة في المنتدى.',
    'VBULLETIN_INTR_HELP_FEATURE_4' =>
        'تقرأ كليجا بيانات قاعدة البيانات من ملف <code>config.php</code> للمنتدى إن كان على الخادم نفسه، أو تستخدم البيانات التي تُدخلها.',
    'VBULLETIN_INTR_HELP_FEATURE_5' =>
        'يفحص تبويب <strong>اختبار الاتصال</strong> بيانات الاتصال وقاعدة البيانات وجدول الأعضاء ومديري المنتدى، ويوضّح طريقة إصلاح كل مشكلة يجدها.',
    'VBULLETIN_INTR_HELP_FEATURE_6' => 'يعمل مع vBulletin 5 وما بعده، ومع vBulletin 3 و4.',

    //
    // how to use it
    //
    'VBULLETIN_INTR_HELP_STEP_TITLE' => 'ربط كليجا بمنتداك',
    'VBULLETIN_INTR_HELP_STEP_1' =>
        'افتح <strong>التكامل مع vBulletin</strong>، وأدخل <strong>عنوان المنتدى</strong>، واختر <strong>إصدار vBulletin</strong>.',
    'VBULLETIN_INTR_HELP_STEP_2' =>
        'إن كان المنتدى على هذا الخادم، فاختر <strong>من ملف config.php للمنتدى</strong>، ثم أدخل <strong>مجلد المنتدى</strong> و<strong>ملف الإعدادات</strong>. وإلا فاختر <strong>إدخالها هنا</strong> وأدخل بيانات قاعدة البيانات.',
    'VBULLETIN_INTR_HELP_STEP_3' =>
        'اضغط <strong>حفظ الإعدادات</strong>، وافتح تبويب <strong>اختبار الاتصال</strong>، واضغط <strong>تشغيل الاختبار</strong>. أصلح ما يذكره حتى تنجح كل الفحوصات.',
    'VBULLETIN_INTR_HELP_STEP_4' =>
        'اضغط <strong>اختبار وتفعيل</strong>. يُسجَّل خروجك، فسجّل الدخول مجددًا بحساب أحد مديري المنتدى.',

    //
    // common questions
    //
    'VBULLETIN_INTR_HELP_FAQ_Q_1' => 'لا أستطيع دخول لوحة التحكم بعد تفعيل التكامل. ماذا أفعل؟',
    'VBULLETIN_INTR_HELP_FAQ_A_1' =>
        'لا يدخل لوحة التحكم إلا الأعضاء الذين مجموعتهم الأساسية في المنتدى هي «المديرون». إن لم ينجح أي حساب منها، أو كانت قاعدة بيانات المنتدى متوقفة، فأضف <code dir="ltr">define(\'DISABLE_INTR\', true);</code> إلى آخر ملف <code>config.php</code>، فتعمل حسابات كليجا مجددًا إلى أن تحذف هذا السطر.',
    'VBULLETIN_INTR_HELP_FAQ_Q_2' => 'لا يستطيع أحد الأعضاء تسجيل الدخول. ما الذي أراجعه؟',
    'VBULLETIN_INTR_HELP_FAQ_A_2' =>
        'تأكد من أن العضو غير محظور ويستطيع تسجيل الدخول إلى المنتدى، ثم شغّل <strong>اختبار الاتصال</strong>. فإن لم تصل كليجا إلى قاعدة بيانات المنتدى، فلن يتمكن أحد من تسجيل الدخول.',
    'VBULLETIN_INTR_HELP_FAQ_Q_3' => 'تظهر الأسماء علامات استفهام أو رموزًا غريبة. كيف أصلحها؟',
    'VBULLETIN_INTR_HELP_FAQ_A_3' =>
        'اضبط <strong>ترميز الأحرف</strong> على الترميز الذي يستخدمه المنتدى. المنتديات القديمة التي تحفظ نصوص UTF-8 في جداول latin1 تحتاج إلى <code>latin1</code>. ومع <strong>من ملف config.php للمنتدى</strong> تستخدم كليجا قيمة <code dir="ltr">$config[\'Mysqli\'][\'charset\']</code> في المنتدى، أو utf8 إن لم تُضبط.',
    'VBULLETIN_INTR_HELP_FAQ_Q_4' => 'ماذا يحدث لحسابات كليجا الحالية؟',
    'VBULLETIN_INTR_HELP_FAQ_A_4' =>
        'تبقى في قاعدة البيانات، لكن لا يمكن تسجيل الدخول بها ما دام التكامل مفعّلًا، وتعود عند تعطيله.',

    //
    // good to know
    //
    'VBULLETIN_INTR_HELP_TIP_1' =>
        'خصّص لكليجا مستخدم قاعدة بيانات مستقلًا لا يملك إلا صلاحية SELECT على جدول <code>user</code> في المنتدى، فكليجا لا تحتاج إلى أكثر من ذلك.',
    'VBULLETIN_INTR_HELP_TIP_2' =>
        'ما دام التكامل مفعّلًا، لا تُحفظ بيانات اتصال جديدة إلا إذا نجح الاختبار بها، فلا يمنع خطأ كتابي أحدًا من الدخول.',
    'VBULLETIN_INTR_HELP_TIP_3' =>
        'استخدم عنوان المنتدى الذي يبدأ بـ <code dir="ltr">https://</code> إن توفّر، ليستعيد الأعضاء كلمات مرورهم عبر اتصال آمن.',

    //
    // be careful
    //
    'VBULLETIN_INTR_HELP_WARNING_1' =>
        'ترتبط الملفات بأرقام المستخدمين. بعد تفعيل التكامل تصبح الملفات التي رفعها حساب كليجا رقم 7 ملكًا لعضو المنتدى رقم 7، ويحدث العكس عند تعطيله.',
    'VBULLETIN_INTR_HELP_WARNING_2' =>
        'يُسجَّل خروج المديرين فور تفعيل التكامل أو تعطيله. أما الأعضاء الآخرون المسجَّلون في تلك اللحظة فيبقون بحساباتهم السابقة حتى يسجّلوا الخروج أو تنتهي مدة دخولهم.',
    'VBULLETIN_INTR_HELP_WARNING_3' =>
        'لا يفتح هذه الصفحة إلا مؤسس، لأن التكامل يحدد من يستطيع تسجيل الدخول ومن يصبح مؤسسًا. ويصبح مديرو المنتدى مؤسسين.',
];
