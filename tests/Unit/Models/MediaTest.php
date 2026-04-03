<?php
namespace AllI1D\Tests\Unit\Models;

use AllI1D\Models\Media;
use AllI1D\Tests\UnitTestCase;

class MediaTest extends UnitTestCase {

	public function test_constructor_with_empty_array_sets_id_to_null(): void {
		$media = new Media( [] );
		$this->assertNull( $media->id );
	}

	public function test_constructor_with_empty_array_sets_url_to_empty_string(): void {
		$media = new Media( [] );
		$this->assertSame( '', $media->url );
	}

	public function test_constructor_without_arguments_uses_defaults(): void {
		$media = new Media();
		$this->assertNull( $media->id );
		$this->assertSame( '', $media->url );
	}

	public function test_constructor_sets_id_from_attributes(): void {
		$media = new Media( [ 'id' => 42 ] );
		$this->assertSame( 42, $media->id );
	}

	public function test_constructor_casts_id_to_int(): void {
		$media = new Media( [ 'id' => '7' ] );
		$this->assertSame( 7, $media->id );
	}

	public function test_constructor_sets_url_from_attributes(): void {
		$media = new Media( [ 'url' => 'https://example.com/file.zip' ] );
		$this->assertSame( 'https://example.com/file.zip', $media->url );
	}

	public function test_constructor_casts_url_to_string(): void {
		$media = new Media( [ 'url' => 123 ] );
		$this->assertSame( '123', $media->url );
	}

	public function test_found_default_value_is_false_string(): void {
		$media = new Media();
		$this->assertSame( 'false', $media->found );
	}

	public function test_title_default_value_is_empty_string(): void {
		$media = new Media();
		$this->assertSame( '', $media->title );
	}

	public function test_type_default_value_is_empty_string(): void {
		$media = new Media();
		$this->assertSame( '', $media->type );
	}

	public function test_cover_image_default_value_is_empty_string(): void {
		$media = new Media();
		$this->assertSame( '', $media->cover_image );
	}

	public function test_public_properties_can_be_set_directly(): void {
		$media        = new Media();
		$media->found = 'true';
		$media->title = 'Inception';
		$media->type  = 'movie';

		$this->assertSame( 'true', $media->found );
		$this->assertSame( 'Inception', $media->title );
		$this->assertSame( 'movie', $media->type );
	}
}
