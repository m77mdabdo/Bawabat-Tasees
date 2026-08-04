<?php

/**
 * Every static (non-database) UI string on the public site, extracted
 * here so the English translation task could give each one a real,
 * professional counterpart in lang/en/site.php rather than leaving
 * hardcoded Arabic in the Blade views. Dashboard/admin UI is
 * intentionally NOT covered — Arabic-only for staff is a standing
 * decision, out of scope for translation.
 */
return [

    'brand' => [
        'name' => 'بوابة تأسيس الشركات',
    ],

    'nav' => [
        'home' => 'الرئيسية',
        'services' => 'خدماتنا',
        'all_services' => 'كل الخدمات',
        'no_services' => 'لا توجد خدمات متاحة حالياً.',
        'countries' => 'الدول',
        'blog' => 'المدونة',
        'about' => 'من نحن',
        'contact' => 'تواصل معنا',
        'open_menu' => 'فتح القائمة',
        'close_menu' => 'إغلاق القائمة',
        'switch_to_english' => 'English',
        'switch_to_arabic' => 'العربية',
    ],

    'footer' => [
        'rights' => 'جميع الحقوق محفوظة.',
    ],

    'common' => [
        'learn_more' => 'اعرف المزيد',
        'read_more' => 'اقرأ المزيد',
        'whatsapp' => 'واتساب',
        'flagship_badge' => 'خدمة رئيسية',
        'honeypot_label' => 'اتركه فارغًا',
    ],

    'home' => [
        'hero_eyebrow' => 'بوابتك الموثوقة للاستثمار في السعودية',
        'hero_heading' => 'أسس شركتك في السعودية بثقة',
        'hero_subheading' => 'نرافقك من الاستشارة الأولى وحتى استخراج السجل التجاري وانطلاق أعمالك — بخبرة محلية ودعم كامل في كل خطوة.',
        'cta_start' => 'ابدأ تأسيس شركتك',
        'cta_consultation' => 'احجز استشارة مجانية',
        'cta_whatsapp' => 'تواصل عبر واتساب',

        'services_eyebrow' => 'خدماتنا',
        'services_heading' => 'كل ما تحتاجه لتأسيس شركتك',
        'services_cta' => 'عرض كل الخدمات',

        'why_invest_eyebrow' => 'لماذا السعودية',
        'why_invest_heading' => 'لماذا تستثمر في السعودية',
        'why_invest_cta' => 'اقرأ المزيد',

        'formation_eyebrow' => 'خطوات التأسيس',
        'formation_heading' => 'رحلتك نحو تأسيس شركتك',
        'formation_cta' => 'التفاصيل الكاملة',

        'testimonials_eyebrow' => 'آراء عملائنا',
        'testimonials_heading' => 'ماذا يقول عملاؤنا',
        'testimonial_slide_label' => 'الشهادة رقم',

        'articles_eyebrow' => 'المدونة',
        'articles_heading' => 'أحدث المقالات',
        'articles_cta' => 'عرض كل المقالات',

        'final_cta_heading' => 'جاهز لتأسيس شركتك في السعودية؟',
        'final_cta_subheading' => 'تواصل معنا اليوم وابدأ رحلتك نحو الاستثمار في أسرع الاقتصادات نمواً بالمنطقة.',
    ],

    'services' => [
        'index_eyebrow' => 'خدماتنا',
        'index_heading' => 'كل ما تحتاجه لتأسيس شركتك في السعودية',
        'index_subheading' => 'نقدم مجموعة متكاملة من الخدمات المصممة لمرافقتك في كل خطوة، من الاستشارة الأولى وحتى انطلاق أعمالك.',
        'index_empty' => 'لا توجد خدمات متاحة حالياً. يرجى العودة لاحقاً.',

        'requirements_heading' => 'المتطلبات',
        'process_heading' => 'خطوات العمل',
        'cta_heading' => 'جاهز لتبدأ؟',
        'cta_subheading' => 'احجز استشارتك المجانية الآن وابدأ رحلة تأسيس شركتك.',
        'cta_button' => 'احجز استشارة مجانية',
        'back_link' => 'العودة لجميع الخدمات',
    ],

    'countries' => [
        'eyebrow' => 'نطاق العمل',
        'heading' => 'الدول التي نخدمها',
        'subheading' => 'نساعد المستثمرين من مختلف الدول على تأسيس شركاتهم في المملكة العربية السعودية.',
        'empty' => 'لا توجد دول مضافة حالياً. يرجى العودة لاحقاً.',
    ],

    'faqs' => [
        'eyebrow' => 'الأسئلة الشائعة',
        'heading' => 'لديك سؤال؟ لدينا الإجابة',
        'subheading' => 'إجابات على أكثر الأسئلة شيوعاً حول تأسيس الشركات في السعودية.',
        'empty' => 'لا توجد أسئلة شائعة متاحة حالياً. يرجى العودة لاحقاً.',
    ],

    'articles' => [
        'index_eyebrow' => 'المدونة',
        'index_heading' => 'مقالات ونصائح لرواد الأعمال',
        'index_subheading' => 'آخر المستجدات والنصائح حول تأسيس الشركات والاستثمار في السعودية.',
        'index_empty' => 'لا توجد مقالات منشورة حالياً. يرجى العودة لاحقاً.',

        'back_link' => 'العودة لجميع المقالات',
        'comments_heading' => 'التعليقات',
        'comments_empty' => 'لا توجد تعليقات بعد. كن أول من يعلّق.',
        'comment_form_heading' => 'أضف تعليقًا',
        'name_label' => 'الاسم',
        'email_label' => 'البريد الإلكتروني',
        'comment_label' => 'تعليقك',
        'comment_privacy_note' => 'لن يظهر بريدك الإلكتروني للعامة. تخضع جميع التعليقات للمراجعة قبل النشر.',
        'submit_comment' => 'إرسال التعليق',
    ],

    'consultation' => [
        'eyebrow' => 'ابدأ رحلتك',
        'heading' => 'طلب استشارة',
        'subheading' => 'أخبرنا عن مشروعك وسيتواصل معك فريقنا في أقرب وقت ممكن.',
        'full_name' => 'الاسم الكامل',
        'phone' => 'رقم الهاتف',
        'whatsapp_optional' => 'رقم واتساب (اختياري)',
        'email' => 'البريد الإلكتروني',
        'nationality_optional' => 'الجنسية (اختياري)',
        'country_of_residence_optional' => 'بلد الإقامة (اختياري)',
        'service_label' => 'الخدمة المطلوبة',
        'service_placeholder' => 'اختر الخدمة',
        'activity_optional' => 'النشاط التجاري المطلوب (اختياري)',
        'owns_external_company' => 'لدي شركة خارج المملكة حاليًا',
        'message_optional' => 'رسالتك (اختياري)',
        'consent' => 'أوافق على التواصل معي بخصوص طلبي',
        'submit' => 'إرسال الطلب',
    ],

    'contact' => [
        'eyebrow' => 'نحن هنا لمساعدتك',
        'heading' => 'تواصل معنا',
        'subheading' => 'لديك سؤال أو استفسار؟ راسلنا وسنرد عليك في أقرب وقت ممكن.',
        'info_heading' => 'بيانات التواصل',
        'phone_label' => 'الهاتف',
        'whatsapp_label' => 'واتساب',
        'email_label' => 'البريد الإلكتروني',
        'address_label' => 'العنوان',
        'full_name' => 'الاسم الكامل',
        'email' => 'البريد الإلكتروني',
        'phone_optional' => 'رقم الهاتف (اختياري)',
        'message' => 'رسالتك',
        'consent' => 'أوافق على التواصل معي بخصوص رسالتي',
        'submit' => 'إرسال الرسالة',
    ],

    'about' => [
        'meeting_photo_alt' => 'اجتماع عمل بين فريقنا وأحد المستثمرين',
        'office_photo_alt' => 'مبنى مكتبي حديث في المملكة العربية السعودية',
        'expertise_heading' => 'خبرتنا',
        'expertise_body' => 'فريق متخصص يرافق المستثمرين ورواد الأعمال في كل مراحل تأسيس الشركات في السعودية.',
        'compliance_heading' => 'التزامنا بالامتثال',
        'compliance_body' => 'نحرص على مطابقة كل إجراء لأنظمة وزارة الاستثمار والجهات المختصة، دون اختصارات.',
        'support_heading' => 'دعمك خطوة بخطوة',
        'support_body' => 'من الاستشارة الأولى وحتى استخراج السجل التجاري، نبقى معك في كل خطوة.',
        'tour_eyebrow' => 'لمحة عن بيئة عملنا',
        'tour_heading' => 'جولة سريعة داخل مكاتبنا',
        'play_video' => 'تشغيل الفيديو',
        'video_poster_alt' => 'لقطة من فيديو مكاتبنا',
    ],

];
