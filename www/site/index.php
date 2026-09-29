<?php
$pageTitle = 'Vikings — Franchise Opportunities';
$pageDescription = 'For three generations, Vikings has fed coastal families around one hearth. Apply to open the next one — franchise opportunities available now.';
require __DIR__.'/includes/header.php';
?>
        <section class="hero">
            <div class="container">
                <p class="eyebrow">Franchise With Vikings</p>
                <h1>Bring the hearth to your harbor.</h1>
                <p class="lead">For three generations, Vikings has fed coastal families around a single wood-fired hearth. We're looking for owners ready to light that fire in their own town &mdash; a proven menu, a loyal build, and long tables that never go empty.</p>
                <div class="actions">
                    <a href="<?= PORTAL_URL ?>/register" class="btn">Apply as a franchisee</a>
                    <a href="<?= PORTAL_URL ?>/login" class="link">Log in</a>
                </div>
            </div>
        </section>

        <section class="story container">
            <p>Vikings began as a single wood-fired hearth on the harbor &mdash; the kind of fire you cook a whole shoulder of lamb over, slowly, while the tide comes in. Three generations later, that hearth is still the center of the room.</p>
            <p>Every board that leaves our kitchen is built for sharing: smoked, cured or roasted the way coastal families have fed each other for a thousand winters, plated for a Tuesday as much as a celebration. That is the business we are franchising &mdash; not just a menu, but a room people keep coming back to.</p>
        </section>

        <section class="cta">
            <div class="container">
                <h2>Ready to open your own hearth?</h2>
                <p>Franchisees get a proven menu, a recognizable brand, and hands-on support from application to opening night.</p>
                <a href="<?= PORTAL_URL ?>/register" class="btn">Start your application</a>
            </div>
        </section>
<?php require __DIR__.'/includes/footer.php'; ?>
