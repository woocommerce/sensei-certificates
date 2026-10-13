<?php
/**
 * Certificate admin search tests.
 *
 * @package Sensei_Certificates
 */

/**
 * Tests certificate search through real WordPress queries.
 */
class WooThemes_Sensei_Certificates_Test extends WP_UnitTestCase {

	/**
	 * Search matches certificate numbers and related student and course fields.
	 *
	 * @covers WooThemes_Sensei_Certificates::search_certificates
	 * @dataProvider search_terms
	 */
	public function testSearchCertificates_SearchTermGiven_ReturnsMatchingCertificates( $term, $expected_titles ) {
		// Arrange.
		$student_id = self::factory()->user->create(
			array(
				'user_login'   => 'student_login',
				'display_name' => 'Ada Lovelace',
				'user_email'   => 'ada@example.org',
			)
		);
		$course_id  = self::factory()->post->create(
			array(
				'post_type'  => 'course',
				'post_title' => 'Advanced Computing',
			)
		);
		self::factory()->post->create(
			array(
				'post_type'  => 'certificate',
				'post_title' => 'abcdefgh',
				'meta_input' => array(
					'learner_id' => $student_id,
					'course_id'  => $course_id,
				),
			)
		);
		self::factory()->post->create( array( 'post_type' => 'certificate', 'post_title' => 'abc123ef' ) );

		// Act.
		$query = $this->search( $term, array( 'fields' => 'all' ) );

		// Assert.
		self::assertSame( $expected_titles, wp_list_pluck( $query->posts, 'post_title' ) );
	}

	/**
	 * Search treats wildcard characters in course titles literally.
	 *
	 * @covers WooThemes_Sensei_Certificates::search_certificates
	 * @dataProvider literal_search_terms
	 */
	public function testSearchCertificates_WildcardCharactersGiven_MatchesLiteralText( $course_title, $term, $expected_titles ) {
		// Arrange.
		$course_id = self::factory()->post->create( array( 'post_type' => 'course', 'post_title' => $course_title ) );
		self::factory()->post->create(
			array(
				'post_type'  => 'certificate',
				'post_title' => 'abc123ef',
				'meta_input' => array( 'course_id' => $course_id ),
			)
		);

		// Act.
		$query = $this->search( $term, array( 'fields' => 'all' ) );

		// Assert.
		self::assertSame( $expected_titles, wp_list_pluck( $query->posts, 'post_title' ) );
	}

	/**
	 * Related matches respect pagination and status filters without duplicate rows.
	 *
	 * @covers WooThemes_Sensei_Certificates::search_certificates
	 */
	public function testSearchCertificates_PaginatedSearchGiven_ReturnsAccurateTotalsAndPage() {
		// Arrange.
		$student_id = self::factory()->user->create( array( 'display_name' => 'Computing Student' ) );
		$course_id  = self::factory()->post->create( array( 'post_type' => 'course', 'post_title' => 'Computing' ) );
		$args       = array(
			'post_type'  => 'certificate',
			'post_title' => 'Computing certificate',
			'meta_input' => array( 'learner_id' => $student_id, 'course_id' => $course_id ),
		);
		$ids        = self::factory()->post->create_many( 3, $args );
		add_post_meta( $ids[0], 'learner_id', $student_id );
		self::factory()->post->create( array_merge( $args, array( 'post_status' => 'trash' ) ) );

		// Act.
		$query = $this->search( 'Computing', array( 'posts_per_page' => 2, 'paged' => 2, 'orderby' => 'ID', 'order' => 'ASC' ) );

		// Assert.
		self::assertSame( array( $ids[2] ), $query->posts, 'The second page should contain the remaining certificate.' );
		self::assertSame( 3, (int) $query->found_posts, 'Only published matching certificates should count.' );
	}

	/**
	 * Searches do not widen unrelated queries.
	 *
	 * @covers WooThemes_Sensei_Certificates::search_certificates
	 * @dataProvider unrelated_queries
	 */
	public function testSearchCertificates_UnrelatedQueryGiven_DoesNotMatchStudent( $post_type, $admin, $main ) {
		// Arrange.
		$student_id = self::factory()->user->create( array( 'display_name' => 'Unique Student' ) );
		self::factory()->post->create(
			array(
				'post_type'  => $post_type,
				'post_title' => 'unrelated',
				'meta_input' => array( 'learner_id' => $student_id ),
			)
		);

		// Act.
		$query = $this->search( 'Unique', array( 'post_type' => $post_type ), $admin, $main );

		// Assert.
		self::assertSame( array(), $query->posts );
	}

	/**
	 * Run a list search while restoring the main query and screen afterwards.
	 *
	 * @param string $term Search term.
	 * @param array  $args Query arguments.
	 * @param bool   $admin Whether to use an admin screen.
	 * @param bool   $main Whether this is the main query.
	 * @return WP_Query
	 */
	private function search( $term, $args = array(), $admin = true, $main = true ) {
		$previous_query  = $GLOBALS['wp_the_query'];
		$previous_screen = get_current_screen();
		$query           = new WP_Query();
		set_current_screen( $admin ? 'edit-certificate' : 'front' );
		if ( $main ) {
			$GLOBALS['wp_the_query'] = $query;
		}

		try {
			$query->query(
				array_merge(
					array( 'post_type' => 'certificate', 's' => $term, 'fields' => 'ids' ),
					$args
				)
			);
		} finally {
			$GLOBALS['wp_the_query'] = $previous_query;
			$GLOBALS['current_screen'] = $previous_screen;
		}

		return $query;
	}

	/**
	 * Representative search fields and WordPress term combinations.
	 *
	 * @return array
	 */
	public function search_terms() {
		return array(
			'number without student or course links'    => array( '123ef', array( 'abc123ef' ) ),
			'exclusion without student or course links' => array( '123ef -Lovelace', array( 'abc123ef' ) ),
			'display name'                              => array( 'Lovelace', array( 'abcdefgh' ) ),
			'username'                                  => array( 'student_login', array( 'abcdefgh' ) ),
			'email'                                     => array( 'ada@example.org', array( 'abcdefgh' ) ),
			'course title'                              => array( 'Computing', array( 'abcdefgh' ) ),
			'quoted name'                               => array( '"Ada Lovelace"', array( 'abcdefgh' ) ),
			'combined fields'                           => array( 'Lovelace Computing', array( 'abcdefgh' ) ),
			'excluded term absent from certificate'     => array( 'Computing -unrelated', array( 'abcdefgh' ) ),
			'excluded student'                          => array( 'Computing -Lovelace', array() ),
			'excluded course'                           => array( 'Lovelace -Computing', array() ),
			'unmatched term'                            => array( 'Lovelace Biology', array() ),
			'quote escaping'                            => array( "' OR 1=1 --", array() ),
		);
	}

	/**
	 * Literal wildcard characters and text that SQL wildcards would otherwise match.
	 *
	 * @return array
	 */
	public function literal_search_terms() {
		return array(
			'literal percent and underscore' => array( 'Computing 100%_complete', '100%_complete', array( 'abc123ef' ) ),
			'percent must not match text'     => array( 'Computing 100extra_complete', '100%_complete', array() ),
			'underscore must not match text'  => array( 'Computing 100%Xcomplete', '100%_complete', array() ),
		);
	}

	/**
	 * Queries outside the Certificates admin list.
	 *
	 * @return array
	 */
	public function unrelated_queries() {
		return array(
			'front end'       => array( 'certificate', false, true ),
			'secondary query' => array( 'certificate', true, false ),
			'posts list'      => array( 'post', true, true ),
			'templates list'  => array( 'certificate_template', true, true ),
		);
	}
}
