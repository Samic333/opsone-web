<?php /** OpsOne — Contact */ ?>
<div class="info-page">
    <div class="info-page-inner">
        <div class="section-label">✦ Contact</div>
        <h1>Contact Us</h1>
        <p class="lead">Reach out to the <?= e($brand['product_name']) ?> team for inquiries, demo requests, or support.</p>

        <div class="contact-grid">
            <div class="contact-form">
                <div class="info-card">
                    <h3>Send a Message</h3>
                    <form method="POST" action="/contact">
                        <div class="form-group">
                            <label for="contact-name">Full Name</label>
                            <input type="text" id="contact-name" name="name" placeholder="Your name" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-email">Email Address</label>
                            <input type="email" id="contact-email" name="email" placeholder="your@email.com" required>
                        </div>
                        <div class="form-group">
                            <label for="contact-org">Airline / Organization</label>
                            <input type="text" id="contact-org" name="organization" placeholder="Your airline or organization">
                        </div>
                        <div class="form-group">
                            <label for="contact-type">Inquiry Type</label>
                            <select id="contact-type" name="type">
                                <option value="demo">Request Internal Demo</option>
                                <option value="support">Technical Support</option>
                                <option value="onboarding">Airline Onboarding</option>
                                <option value="general">General Inquiry</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="contact-message">Message</label>
                            <textarea id="contact-message" name="message" placeholder="Describe your inquiry..." required></textarea>
                        </div>
                        <button type="submit" class="pub-btn pub-btn-primary pub-btn-large" style="width: 100%;">Send Message</button>
                    </form>
                </div>
            </div>
            <div>
                <h3 style="margin-bottom: 24px;">Direct Contact</h3>
                <div class="contact-info-item">
                    <div class="contact-info-icon">📧</div>
                    <div>
                        <h4>Email</h4>
                        <p><a href="mailto:<?= e($brand['support_email']) ?>"><?= e($brand['support_email']) ?></a></p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon">🏢</div>
                    <div>
                        <h4>Organization</h4>
                        <p><?= e($brand['company_name']) ?></p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon">🌐</div>
                    <div>
                        <h4>Website</h4>
                        <p><a href="/home"><?= e($brand['website_url']) ?></a></p>
                    </div>
                </div>
                <div class="info-card" style="margin-top: 32px;">
                    <h3>🔒 Internal Access</h3>
                    <p>If you're an authorized airline user, use the <a href="/login">Airline Login</a> to access the admin portal or <a href="/install">Install Page</a> to set up the iPad app.</p>
                </div>
            </div>
        </div>
    </div>
</div>
