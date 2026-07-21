<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class NK9_Entries_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'entry',
				'plural'   => 'entries',
				'ajax'     => false,
			)
		);
	}

	public function get_columns() {
		return array(
			'created_at'    => 'Submitted',
			'first_name'    => 'First Name',
			'last_name'     => 'Last Name',
			'dog_name'      => 'Dog',
			'email'         => 'Email',
			'entry_method'  => 'Method',
			'submission'    => 'Submission',
			'status'        => 'Status',
		);
	}

	public function get_sortable_columns() {
		return array(
			'created_at' => array( 'created_at', true ),
			'last_name'  => array( 'last_name', false ),
			'dog_name'   => array( 'dog_name', false ),
		);
	}

	public function prepare_items() {
		global $wpdb;
		$table = $wpdb->prefix . 'nk9_contest_entries';

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$orderby = ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], array( 'created_at', 'last_name', 'dog_name' ), true )
			? sanitize_sql_orderby( $_GET['orderby'] )
			: 'created_at';
		$order = ! empty( $_GET['order'] ) && 'asc' === strtolower( $_GET['order'] ) ? 'ASC' : 'DESC';

		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		$where  = '';
		$params = array();
		if ( $search ) {
			$like   = '%' . $wpdb->esc_like( $search ) . '%';
			$where  = 'WHERE first_name LIKE %s OR last_name LIKE %s OR dog_name LIKE %s OR email LIKE %s';
			$params = array( $like, $like, $like, $like );
		}

		$total_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		$total     = $params ? $wpdb->get_var( $wpdb->prepare( $total_sql, $params ) ) : $wpdb->get_var( $total_sql );

		$sql        = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$sql_params = array_merge( $params, array( $per_page, $offset ) );
		$this->items = $wpdb->get_results( $wpdb->prepare( $sql, $sql_params ), ARRAY_A );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => (int) $total,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total / $per_page ),
			)
		);
	}

	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'created_at':
				return esc_html( mysql2date( 'M j, Y g:ia', $item['created_at'] ) );
			case 'entry_method':
				return esc_html( ucfirst( $item['entry_method'] ) );
			case 'status':
				return esc_html( ucfirst( $item['status'] ) );
			case 'submission':
				if ( 'instagram' === $item['entry_method'] && ! empty( $item['instagram_url'] ) ) {
					return '<a href="' . esc_url( $item['instagram_url'] ) . '" target="_blank" rel="noopener noreferrer">View post</a>';
				}
				if ( ! empty( $item['dropbox_shared_link'] ) ) {
					return '<a href="' . esc_url( $item['dropbox_shared_link'] ) . '" target="_blank" rel="noopener noreferrer">View video</a>';
				}
				if ( ! empty( $item['dropbox_path'] ) ) {
					return esc_html( $item['dropbox_path'] );
				}
				return '&mdash;';
			default:
				return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
		}
	}

	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$export_url = wp_nonce_url( admin_url( 'admin-post.php?action=nk9_export_entries' ), 'nk9_export_entries' );
		echo '<div class="alignleft actions">';
		echo '<a href="' . esc_url( $export_url ) . '" class="button">Export all entries (CSV)</a>';
		echo '</div>';
	}
}
