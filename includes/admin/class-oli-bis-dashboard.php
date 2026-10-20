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
}
