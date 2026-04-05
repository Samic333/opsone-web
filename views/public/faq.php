<?php /** OpsOne — FAQ */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ FAQ</div>
        <h1>Frequently Asked Questions</h1>
        <p class="lead">Common questions about <?= e($brand['product_name']) ?> for airline administrators and crew.</p>

        <h2>General</h2>
        <div class="faq-item open">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                What is <?= e($brand['product_name']) ?>?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p><?= e($brand['product_name']) ?> is a secure, internal airline operations platform for iPad. It provides crew members with tools for duty reporting, roster viewing, flight packages, document access, safety reporting, and more. It is not a public consumer app — it is designed exclusively for authorized airline personnel.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                Is this app available on the App Store?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>No. <?= e($brand['product_name']) ?> is distributed internally through a secure, enterprise-style website installation. It is not available on the App Store or TestFlight. This ensures that only authorized airline personnel can access the app.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                Which devices are supported?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>The app is designed for iPad with iPadOS 16.0 or later. It also works on iPhone, but iPad is the primary target with optimized sidebar navigation and larger layout.</p>
            </div>
        </div>

        <h2>Installation</h2>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                How do I install the app?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>Log in to the <?= e($brand['product_name']) ?> install page with your airline credentials, then tap the install button. After downloading, you may need to trust the enterprise developer certificate in your iPad settings. See the <a href="/install-info">deployment guide</a> for full instructions.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                Why does it say "Untrusted Enterprise Developer"?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>This is normal for enterprise-distributed apps. Go to Settings → General → VPN & Device Management, find the enterprise certificate, and tap "Trust". This only needs to be done once.</p>
            </div>
        </div>

        <h2>Data & Security</h2>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                Can other airlines see my data?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>Absolutely not. <?= e($brand['product_name']) ?> uses complete multi-tenant isolation. Each airline's data is stored separately and there is no mechanism for cross-airline data access.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                Can I use the app offline?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>Yes. Documents and manuals that have been synced are stored locally on your iPad. You can read them without internet. However, you'll need connectivity to sync new content or submit reports.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                What data is logged?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>Login attempts, file downloads, sync events, and administrative actions are logged for security and compliance purposes. This audit trail helps your airline meet regulatory requirements.</p>
            </div>
        </div>

        <h2>Administration</h2>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                How do I add new users?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>Log in to the admin portal, go to Users → Create User. Fill in the user's details, assign a role (Pilot, Cabin Crew, Engineer, etc.), and set their status to Active. They can then log in to the iPad app.</p>
            </div>
        </div>
        <div class="faq-item">
            <div class="faq-question" onclick="this.parentElement.classList.toggle('open')">
                How do I upload documents?
                <span class="faq-arrow">▾</span>
            </div>
            <div class="faq-answer">
                <p>Go to Documents → Upload. Select the file, choose a category (Manuals, Notices, Training, etc.), set a version number, and click Upload. Toggle "Published" to make it available for sync.</p>
            </div>
        </div>
    </div>
</div>
