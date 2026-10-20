<?php
/**
 * Admin dashboard.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders 30-day stats and demand lists.
 *
 * @since 0.1.0
 */
class Oli_BIS_Dashboard {

	/**
	 * Renders the dashboard.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render() {
		$totals  = Oli_BIS_Stats::totals( 30 );
		$series  = Oli_BIS_Stats::series( 30 );
		$wanted  = Oli_BIS_Stats::most_wanted( 5 );
		$overdue = Oli_BIS_Stats::most_overdue( 5 );

		echo '<div class="wrap oli-bis-dashboard">';
		echo '<h1>' . esc_html__( 'Stock Notification dashboard', 'oli-back-in-stock-notifications' ) . '</h1>';
		echo '<p>' . esc_html__( 'Last 30 days', 'oli-back-in-stock-notifications' ) . '</p>';
		echo '<div class="oli-bis-cards">';
		$this->card( __( 'Signups', 'oli-back-in-stock-notifications' ), $totals['signups'] );
		$this->card( __( 'Notifications sent', 'oli-back-in-stock-notifications' ), $totals['notifications'] );
		$this->card( __( 'Conversions', 'oli-back-in-stock-notifications' ), $totals['conversions'] );
		echo '</div>';

		echo '<h2>' . esc_html__( 'Daily activity', 'oli-back-in-stock-notifications' ) . '</h2>';
		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'Date', 'oli-back-in-stock-notifications' ) . '</th>';
		echo '<th>' . esc_html__( 'Signups', 'oli-back-in-stock-notifications' ) . '</th>';
		echo '<th>' . esc_html__( 'Notifications', 'oli-back-in-stock-notifications' ) . '</th>';
		echo '<th>' . esc_html__( 'Conversions', 'oli-back-in-stock-notifications' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $series as $row ) {
			echo '<tr>';
			echo '<td>' . esc_html( $row['stat_date'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['signups'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['notifications'] ) . '</td>';
			echo '<td>' . esc_html( (string) $row['conversions'] ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';

		echo '<div class="oli-bis-columns">';
		$this->product_list( __( 'Most wanted', 'oli-back-in-stock-notifications' ), $wanted );
		$this->product_list( __( 'Longest waiting', 'oli-back-in-stock-notifications' ), $overdue, true );
		echo '</div>';
		$this->render_migration();
		echo '</div>';
	}

	/**
	 * Renders a KPI card.
	 *
	 * @since 0.1.0
	 * @param string $label Label.
	 * @param int    $value Value.
	 * @return void
	 */
	private function card( $label, $value ) {
		echo '<div class="oli-bis-card"><strong>' . esc_html( (string) $value ) . '</strong><span>' . esc_html( $label ) . '</span></div>';
	}

	/**
	 * Renders a product insight list.
	 *
	 * @since 0.1.0
	 * @param string                         $title  Title.
	 * @param array<int,array<string,mixed>> $rows   Rows.
	 * @param bool                           $dates  Show first signup.
	 * @return void
	 */
	private function product_list( $title, $rows, $dates = false ) {
		echo '<div class="oli-bis-column"><h2>' . esc_html( $title ) . '</h2><ul>';

		if ( empty( $rows ) ) {
			echo '<li>' . esc_html__( 'No data yet.', 'oli-back-in-stock-notifications' ) . '</li>';
		}

		foreach ( $rows as $row ) {
			$product_id = ! empty( $row['variation_id'] ) ? (int) $row['variation_id'] : (int) $row['product_id'];
			$product    = wc_get_product( $product_id );
			$name       = $product ? $product->get_name() : (string) $product_id;
			$meta       = isset( $row['total'] ) ? (int) $row['total'] : 0;

			if ( $dates && ! empty( $row['first_signup'] ) ) {
				$meta .= ' · ' . $row['first_signup'];
			}

			echo '<li>' . esc_html( $name ) . ' <span>(' . esc_html( (string) $meta ) . ')</span></li>';
		}

		echo '</ul></div>';
	}

	/**
	 * PixM import tools. Never deletes the source table.
	 *
	 * @since 0.1.1
	 * @return void
	 */
	private function render_migration() {
		$detect = Oli_BIS_Migrator::detect();

		echo '<h2>' . esc_html__( 'PixM data import', 'oli-back-in-stock-notifications' ) . '</h2>';

		if ( ! empty( $_GET['oli_bis_migrated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$report = get_transient( 'oli_bis_migrate_report' );
			if ( is_array( $report ) ) {
				echo '<div class="notice notice-success"><p>';
				echo esc_html(
					sprintf(
						/* translators: 1: import count, 2: skip count, 3: invalid count */
						__( 'Import result — import: %1$d, skip: %2$d, invalid: %3$d. Source data was not deleted.', 'oli-back-in-stock-notifications' ),
						(int) $report['import'],
						(int) $report['skip'],
						(int) $report['invalid']
					)
				);
				if ( ! empty( $report['dry_run'] ) ) {
					echo ' ' . esc_html__( 'This was a dry run.', 'oli-back-in-stock-notifications' );
				}
				echo '</p></div>';
			}
		}

		if ( empty( $detect['table_exists'] ) ) {
			echo '<p>' . esc_html__( 'No PixM table (wc_back_in_stock_notifications) was found on this site.', 'oli-back-in-stock-notifications' ) . '</p>';
			return;
		}

		echo '<p>';
		echo esc_html(
			sprintf(
				/* translators: 1: table name, 2: row count */
				__( 'Found %1$s with %2$d subscriber rows. Import copies them into this plugin and leaves the original table untouched.', 'oli-back-in-stock-notifications' ),
				$detect['table'],
				(int) $detect['row_count']
			)
		);
		echo '</p>';

		echo '<form method="post">';
		wp_nonce_field( 'oli_bis_migrate' );
		echo '<button type="submit" class="button" name="oli_bis_migrate" value="dry">' . esc_html__( 'Dry-run import', 'oli-back-in-stock-notifications' ) . '</button> ';
		echo '<button type="submit" class="button button-primary" name="oli_bis_migrate" value="run">' . esc_html__( 'Import subscribers', 'oli-back-in-stock-notifications' ) . '</button>';
		echo '</form>';
	}
}
