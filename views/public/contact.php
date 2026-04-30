<?php /** OpsOne — Request Demo / Contact (premium 9-field form) */
$errors = $errors ?? [];
$values = $values ?? [];
$err = static fn(string $key): string => isset($errors[$key]) ? ' has-error' : '';
$val = static fn(string $key, $default = ''): string => htmlspecialchars((string)($values[$key] ?? $default), ENT_QUOTES);
?>
<div class="info-page">
    <div class="info-page-inner" style="max-width: 1100px;">
        <div class="section-label">Request a Demo</div>
        <h1>See OpsOne With Your Airline's Workflows</h1>
        <p class="lead">
            Tell us about your operation and we'll book a 30-minute walkthrough with your team.
            Web dashboard, iPad crew app, multi-tenant model — all explained against your fleet,
            bases, and roles.
        </p>

        <div class="contact-grid">
            <div class="contact-form">
                <div class="info-card">
                    <h3>Demo Request</h3>
                    <?php if (!empty($flashMsg)): ?>
                        <div class="contact-alert <?= ($flashType ?? '') === 'success' ? 'is-success' : 'is-error' ?>">
                            <span class="contact-alert-icon">
                                <?= ($flashType ?? '') === 'success' ? sidebarIcon('check-badge', 16) : sidebarIcon('exclamation', 16) ?>
                            </span>
                            <span><?= e($flashMsg) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/contact" novalidate>
                        <?= csrfField() ?>

                        <div class="form-row">
                            <div class="form-group<?= $err('contact_name') ?>">
                                <label for="ct-contact-name">Contact Person <span class="req">*</span></label>
                                <input type="text" id="ct-contact-name" name="contact_name"
                                       placeholder="Captain or Manager name"
                                       value="<?= $val('contact_name') ?>" autocomplete="name" required>
                                <?php if (isset($errors['contact_name'])): ?><div class="form-error"><?= e($errors['contact_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group<?= $err('email') ?>">
                                <label for="ct-email">Work Email <span class="req">*</span></label>
                                <input type="email" id="ct-email" name="email"
                                       placeholder="you@yourairline.com"
                                       value="<?= $val('email') ?>" autocomplete="email" required>
                                <?php if (isset($errors['email'])): ?><div class="form-error"><?= e($errors['email']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group<?= $err('phone') ?>">
                                <label for="ct-phone">Phone / WhatsApp <span class="opt">optional</span></label>
                                <input type="tel" id="ct-phone" name="phone"
                                       placeholder="+254 7XX XXX XXX"
                                       value="<?= $val('phone') ?>" autocomplete="tel">
                            </div>
                            <div class="form-group<?= $err('country') ?>">
                                <label for="ct-country">Country <span class="req">*</span></label>
                                <input type="text" id="ct-country" name="country"
                                       placeholder="e.g. Kenya"
                                       value="<?= $val('country') ?>" autocomplete="country-name" required>
                                <?php if (isset($errors['country'])): ?><div class="form-error"><?= e($errors['country']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group<?= $err('company') ?>">
                                <label for="ct-company">Company Name <span class="req">*</span></label>
                                <input type="text" id="ct-company" name="company"
                                       placeholder="Holding company / parent"
                                       value="<?= $val('company') ?>" required>
                                <?php if (isset($errors['company'])): ?><div class="form-error"><?= e($errors['company']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group<?= $err('airline') ?>">
                                <label for="ct-airline">Airline / Operator <span class="req">*</span></label>
                                <input type="text" id="ct-airline" name="airline"
                                       placeholder="e.g. 748 Air Services"
                                       value="<?= $val('airline') ?>" required>
                                <?php if (isset($errors['airline'])): ?><div class="form-error"><?= e($errors['airline']) ?></div><?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group<?= $err('operation') ?>">
                                <label for="ct-operation">Operation Type</label>
                                <select id="ct-operation" name="operation">
                                    <?php
                                    $ops = [
                                        '' => 'Select operation type',
                                        'commercial_scheduled' => 'Commercial Scheduled',
                                        'charter'              => 'Charter',
                                        'cargo'                => 'Cargo',
                                        'corporate'            => 'Corporate / Business Aviation',
                                        'training'             => 'Flight Training',
                                        'other'                => 'Other',
                                    ];
                                    $selected = $values['operation'] ?? '';
                                    foreach ($ops as $k => $label):
                                    ?>
                                        <option value="<?= e($k) ?>" <?= $selected === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['operation'])): ?><div class="form-error"><?= e($errors['operation']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group<?= $err('crew_size') ?>">
                                <label for="ct-crew">Number of Crew / Users</label>
                                <select id="ct-crew" name="crew_size">
                                    <?php
                                    $crew = [
                                        ''         => 'Select crew size',
                                        '1-25'     => 'Up to 25',
                                        '26-100'   => '26 – 100',
                                        '101-300'  => '101 – 300',
                                        '301-1000' => '301 – 1,000',
                                        '1000+'    => '1,000+',
                                    ];
                                    $selectedCrew = $values['crew_size'] ?? '';
                                    foreach ($crew as $k => $label):
                                    ?>
                                        <option value="<?= e($k) ?>" <?= $selectedCrew === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group<?= $err('inquiry_type') ?>">
                            <label for="ct-inquiry">Inquiry Type</label>
                            <select id="ct-inquiry" name="inquiry_type">
                                <?php
                                $types = [
                                    'demo'       => 'Request Demo',
                                    'sales'      => 'Contact Sales',
                                    'onboarding' => 'Airline Onboarding',
                                    'support'    => 'Technical Support',
                                    'general'    => 'General Inquiry',
                                ];
                                $selectedType = $values['inquiry_type'] ?? 'demo';
                                foreach ($types as $k => $label):
                                ?>
                                    <option value="<?= e($k) ?>" <?= $selectedType === $k ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group<?= $err('message') ?>">
                            <label for="ct-message">Message <span class="req">*</span></label>
                            <textarea id="ct-message" name="message"
                                      placeholder="Brief context: current ops tools, biggest pain point, target go-live, anything else we should know."
                                      required><?= $val('message') ?></textarea>
                            <?php if (isset($errors['message'])): ?><div class="form-error"><?= e($errors['message']) ?></div><?php endif; ?>
                        </div>

                        <button type="submit" class="pub-btn pub-btn-primary pub-btn-large" style="width: 100%;">
                            Send Request
                        </button>
                        <p class="form-fineprint">
                            We reply within one business day. We never share or sell contact details.
                        </p>
                    </form>
                </div>
            </div>

            <div>
                <h3 style="margin-bottom: 24px;">What Happens Next</h3>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><?= sidebarIcon('clock', 18) ?></div>
                    <div>
                        <h4>Within 1 business day</h4>
                        <p>We review your request and reply with a calendar link to book the walkthrough.</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><?= sidebarIcon('clipboard-list', 18) ?></div>
                    <div>
                        <h4>30-minute demo</h4>
                        <p>Web dashboard tour, iPad app walkthrough, and Q&amp;A with the team that will run your tenant.</p>
                    </div>
                </div>
                <div class="contact-info-item">
                    <div class="contact-info-icon"><?= sidebarIcon('rocket-launch', 18) ?></div>
                    <div>
                        <h4>Tailored proposal</h4>
                        <p>Module recommendation, migration approach, training plan, and a fixed first-year price.</p>
                    </div>
                </div>

                <div class="info-card" style="margin-top: 32px;">
                    <h3>Direct Contact</h3>
                    <p style="margin-bottom: 8px;">
                        <strong style="color:var(--text-primary);"><?= e($brand['company_name']) ?></strong>
                    </p>
                    <p style="font-size:13px;color:var(--text-secondary);margin-bottom:8px;">
                        Email: <a href="mailto:<?= e($brand['support_email']) ?>"><?= e($brand['support_email']) ?></a>
                    </p>
                    <p style="font-size:13px;color:var(--text-secondary);">
                        Existing client? Use <a href="/login">Client Login</a> or your airline's branded login URL.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
