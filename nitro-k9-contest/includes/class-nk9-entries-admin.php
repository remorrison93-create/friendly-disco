<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class NK9_Entries_Admin {

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$table = new NK9_Entries_Table();
		$table->prepare_items();
		?>
		<div class="wrap">
			<h1>Contest Entries</h1>
			<p>All "Art of the Leash" Clip Contest submissions received through the entry form. Use this list to review entries, verify client status, and pick the Grand Prize and Second Place winners per the Official Rules.</p>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ?? 'nk9-contest-entries' ); ?>" />
				<?php $table->search_box( 'Search entries', 'nk9-entry-search' ); ?>
			</form>
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ?? 'nk9-contest-entries' ); ?>" />
				<?php $table->display(); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed.' );
		}
		check_admin_referer( 'nk9_export_entries' );

		global $wpdb;
		$table_name = $wpdb->prefix . 'nk9_contest_entries';
		$rows       = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY created_at ASC", ARRAY_A );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=nitro-k9-contest-entries-' . gmdate( 'Y-m-d' ) . '.csv' );

		$output = fopen( 'php://output', 'w' );
		fputcsv(
			$output,
			array(
				'ID',
				'Submitted',
				'First Name',
				'Last Name',
				'Dog',
				'Email',
				'Entry Method',
				'Instagram URL',
				'Dropbox Path',
				'Dropbox Link',
				'Agreed to Rules',
				'IP Address',
				'Status',
			)
		);

		foreach ( $rows as $row ) {
			fputcsv(
				$output,
				array(
					$row['id'],
					$row['created_at'],
					$row['first_name'],
					$row['last_name'],
					$row['dog_name'],
					$row['email'],
					$row['entry_method'],
					$row['instagram_url'],
					$row['dropbox_path'],
					$row['dropbox_shared_link'],
					$row['agreed_rules'] ? 'Yes' : 'No',
					$row['ip_address'],
					$row['status'],
				)
			);
		}

		fclose( $output );
		exit;
	}
}
