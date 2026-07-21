<?php
/**
 * Template variables provided by NK9_Shortcode::render():
 * @var array  $settings
 * @var string $status
 * @var string $message
 * @var array  $values
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$logo_url = NITRO_K9_CONTEST_URL . 'assets/images/nitro-k9-contest/assets/images/logo.png';

$format_date = function ( $raw ) {
	try {
		$dt = new DateTime( $raw, new DateTimeZone( 'America/Los_Angeles' ) );
		return $dt->format( 'F j, Y' );
	} catch ( Exception $e ) {
		return $raw;
	}
};

$entry_start_display = $format_date( $settings['entry_start'] );
$entry_end_display   = $format_date( $settings['entry_end'] );

$v = wp_parse_args(
	$values,
	array(
		'first_name' => '',
		'last_name'  => '',
		'dog_name'   => '',
		'email'      => '',
		'method'     => 'instagram',
		'instagram'  => '',
	)
);
?>
<div class="nk9-contest">

	<section class="nk9-hero">
		<div class="nk9-container nk9-hero__inner">
			<img class="nk9-hero__logo" src="<?php echo esc_url( $logo_url ); ?>" alt="Nitro K-9 LLC" />
			<p class="nk9-hero__eyebrow">Nitro K-9 LLC Presents</p>
			<h1 class="nk9-hero__title">Art of the Leash <span>Clip Contest</span></h1>
			<p class="nk9-hero__dates"><?php echo esc_html( $entry_start_display ); ?> &ndash; <?php echo esc_html( $entry_end_display ); ?> (Pacific Time)</p>
			<p class="nk9-hero__sub">Show off what you and your dog have learned together &mdash; win custom hand-drawn or painted portrait artwork by <strong>The Hound &amp; The Human</strong>.</p>
			<a href="#nk9-contest-form" class="nk9-btn nk9-btn--primary">Enter the Contest</a>
		</div>
	</section>

	<section class="nk9-section">
		<div class="nk9-container">
			<h2 class="nk9-section__title">How to Enter</h2>
			<div class="nk9-steps">
				<div class="nk9-step">
					<span class="nk9-step__num">1</span>
					<h3>Record</h3>
					<p>Film your dog demonstrating a trained behavior or skill you learned at Nitro K-9.</p>
				</div>
				<div class="nk9-step">
					<span class="nk9-step__num">2</span>
					<h3>Post or Submit</h3>
					<p>Post publicly on Instagram &mdash; tag <strong><?php echo esc_html( $settings['instagram_handle'] ); ?></strong> and use <strong><?php echo esc_html( $settings['hashtag'] ); ?></strong> &mdash; or submit your clip directly below.</p>
				</div>
				<div class="nk9-step">
					<span class="nk9-step__num">3</span>
					<h3>You're Entered</h3>
					<p>No purchase, payment, referral, or booking required. Limit three (3) video entries per household, per dog.</p>
				</div>
			</div>
		</div>
	</section>

	<section class="nk9-section nk9-section--dark">
		<div class="nk9-container">
			<h2 class="nk9-section__title">Prizes</h2>
			<div class="nk9-prizes">
				<div class="nk9-prize-card nk9-prize-card--grand">
					<span class="nk9-prize-card__tag">Grand Prize</span>
					<h3>Custom Portrait or Painting</h3>
					<p>Your choice of a black &amp; grey hand-drawn portrait or a full-color painting of you and your dog, created by Artist Bex Mare of The Hound &amp; The Human.</p>
					<p class="nk9-prize-card__value">Approx. Retail Value: $500</p>
					<p class="nk9-prize-card__note">Selected by Sponsor/Artist based on training quality, video clarity, and overall impression &mdash; a merit-based selection, not a random drawing.</p>
				</div>
				<div class="nk9-prize-card">
					<span class="nk9-prize-card__tag">Second Place</span>
					<h3>Custom Full-Color Painting</h3>
					<p>A full-color painting of you and your dog, created by Artist Bex Mare of The Hound &amp; The Human.</p>
					<p class="nk9-prize-card__value">Approx. Retail Value: $100</p>
					<p class="nk9-prize-card__note">Awarded by random drawing among eligible entries not selected for the Grand Prize.</p>
				</div>
			</div>
			<p class="nk9-prizes__footnote">No cash alternative. Prizes are non-transferable. Reference photos are subject to Artist approval. See full Official Rules below.</p>
		</div>
	</section>

	<section class="nk9-section" id="nk9-contest-form">
		<div class="nk9-container nk9-container--narrow">
			<h2 class="nk9-section__title">Submit Your Entry</h2>

			<?php if ( 'success' === $status ) : ?>
				<div class="nk9-alert nk9-alert--success" role="status">
					<strong>You're entered!</strong> Thanks for sharing your dog's skills. Watch your email and Instagram/Telegram DMs for winner announcements around August 21, 2026.
				</div>
			<?php elseif ( 'error' === $status && $message ) : ?>
				<div class="nk9-alert nk9-alert--error" role="alert">
					<strong>We couldn't submit your entry:</strong> <?php echo esc_html( $message ); ?>
				</div>
			<?php endif; ?>

			<?php if ( 'success' !== $status ) : ?>
			<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="nk9-form" id="nk9-entry-form" data-max-video-mb="<?php echo esc_attr( $settings['max_video_mb'] ); ?>">
				<input type="hidden" name="action" value="nk9_submit_entry" />
				<?php wp_nonce_field( 'nk9_submit_entry', 'nk9_nonce' ); ?>
				<p class="nk9-honeypot" aria-hidden="true">
					<label>Website <input type="text" name="nk9_website" tabindex="-1" autocomplete="off" /></label>
				</p>

				<div class="nk9-form__row">
					<div class="nk9-field">
						<label for="nk9_first_name">First Name *</label>
						<input type="text" id="nk9_first_name" name="first_name" required value="<?php echo esc_attr( $v['first_name'] ); ?>" />
					</div>
					<div class="nk9-field">
						<label for="nk9_last_name">Last Name *</label>
						<input type="text" id="nk9_last_name" name="last_name" required value="<?php echo esc_attr( $v['last_name'] ); ?>" />
					</div>
				</div>

				<div class="nk9-form__row">
					<div class="nk9-field">
						<label for="nk9_dog_name">Dog's Name *</label>
						<input type="text" id="nk9_dog_name" name="dog_name" required value="<?php echo esc_attr( $v['dog_name'] ); ?>" />
					</div>
					<div class="nk9-field">
						<label for="nk9_email">Email *</label>
						<input type="email" id="nk9_email" name="email" required value="<?php echo esc_attr( $v['email'] ); ?>" />
					</div>
				</div>

				<fieldset class="nk9-field nk9-method">
					<legend>How would you like to submit your clip? *</legend>
					<div class="nk9-method__toggle">
						<label class="nk9-method__option">
							<input type="radio" name="entry_method" value="instagram" <?php checked( 'video' !== $v['method'] ); ?> />
							Instagram post URL
						</label>
						<label class="nk9-method__option">
							<input type="radio" name="entry_method" value="video" <?php checked( 'video' === $v['method'] ); ?> />
							Upload video file
						</label>
					</div>
				</fieldset>

				<div class="nk9-field nk9-method__panel" data-method-panel="instagram">
					<label for="nk9_instagram_url">Instagram Post URL *</label>
					<input type="url" id="nk9_instagram_url" name="instagram_url" placeholder="https://www.instagram.com/p/..." value="<?php echo esc_attr( $v['instagram'] ); ?>" />
					<p class="nk9-field__hint">Your post must be public and include <?php echo esc_html( $settings['instagram_handle'] ); ?> and <?php echo esc_html( $settings['hashtag'] ); ?>.</p>
				</div>

				<div class="nk9-field nk9-method__panel" data-method-panel="video" hidden>
					<label for="nk9_video_file">Video File *</label>
					<input type="file" id="nk9_video_file" name="video_file" accept="video/mp4,video/quicktime,video/webm,video/x-msvideo,.mp4,.mov,.webm,.avi,.m4v" />
					<p class="nk9-field__hint">MP4, MOV, WEBM, or AVI, up to <?php echo esc_html( $settings['max_video_mb'] ); ?>MB. Your video will be securely stored for judging.</p>
				</div>

				<div class="nk9-field nk9-agree">
					<label>
						<input type="checkbox" name="agree_rules" value="1" required />
						I have read and agree to the <a href="#nk9-official-rules">Official Rules</a>, including the entry period, eligibility requirements, and the publicity/usage rights granted to Sponsor. *
					</label>
				</div>

				<button type="submit" class="nk9-btn nk9-btn--primary nk9-btn--block" id="nk9-submit-btn">Submit Entry</button>
			</form>
			<?php endif; ?>
		</div>
	</section>

	<section class="nk9-section nk9-section--dark">
		<div class="nk9-container nk9-container--narrow nk9-referral">
			<h2 class="nk9-section__title">Referral Program Drawing</h2>
			<p>Nitro K-9 Telegram group members can also earn entries into a separate $500 portrait drawing just by referring new clients:</p>
			<ul class="nk9-referral__list">
				<li><strong>Refer a friend:</strong> have them list your name in the "How did you hear about us" field at <a href="https://www.nitrocanine.com" target="_blank" rel="noopener noreferrer">nitrocanine.com</a> to earn one entry &mdash; no limit on referrals.</li>
				<li><strong>No referral? No problem:</strong> leave a Google Review or email a short testimonial to <a href="mailto:admin@nitrocanine.com">admin@nitrocanine.com</a>, then notify us in the Telegram group with <?php echo esc_html( $settings['hashtag'] ); ?> to log your entry.</li>
			</ul>
			<?php if ( ! empty( $settings['telegram_group_url'] ) ) : ?>
				<a href="<?php echo esc_url( $settings['telegram_group_url'] ); ?>" class="nk9-btn nk9-btn--outline" target="_blank" rel="noopener noreferrer">Join the Telegram Group</a>
			<?php endif; ?>
			<p class="nk9-prizes__footnote">Full Referral Program rules are included in the Official Rules below. This drawing runs on its own entry mechanism and does not use the form above.</p>
		</div>
	</section>

	<section class="nk9-section" id="nk9-official-rules">
		<div class="nk9-container nk9-container--narrow">
			<h2 class="nk9-section__title">Official Rules</h2>

			<details class="nk9-rules">
				<summary>Official Rules &mdash; "Art of the Leash" Clip Contest</summary>
				<div class="nk9-rules__body">
					<?php include NITRO_K9_CONTEST_DIR . 'includes/templates/rules-clip-contest.php'; ?>
				</div>
			</details>

			<details class="nk9-rules">
				<summary>Official Rules &mdash; Referral Program Drawing</summary>
				<div class="nk9-rules__body">
					<?php include NITRO_K9_CONTEST_DIR . 'includes/templates/rules-referral-program.php'; ?>
				</div>
			</details>

			<p class="nk9-legal-disclaimer">
				Any and all photos submitted by contest winners for the portrait prizes are subject to Artist approval. Please allow up to two weeks after reference material is approved to receive your artwork. Artwork is delivered digitally (one JPEG and one PNG) from the Artist's email to the email you provide, plus one 8x10 print mailed or available for pickup at Nitro K-9 LLC at your next lesson or playgroup.
			</p>
			<p class="nk9-legal-disclaimer">
				This Contest is in no way sponsored, endorsed, administered by, or associated with Meta (Instagram), TikTok, YouTube (Google), or Telegram.
			</p>
		</div>
	</section>

</div>
