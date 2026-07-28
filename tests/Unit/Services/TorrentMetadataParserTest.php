<?php
namespace AllI1D\Tests\Unit\Services;

use AllI1D\Services\TorrentMetadataParser;
use AllI1D\Tests\UnitTestCase;

class TorrentMetadataParserTest extends UnitTestCase {

	private TorrentMetadataParser $parser;

	protected function setUp(): void {
		parent::setUp();
		$this->parser = new TorrentMetadataParser();
	}

	// -------------------------------------------------------------------------
	// extract_quality
	// -------------------------------------------------------------------------

	public function test_extract_quality_finds_1080p(): void {
		$this->assertSame( '1080p', $this->parser->extract_quality( 'Movie.Title.2020.1080p.FRENCH.x264' ) );
	}

	public function test_extract_quality_finds_720p(): void {
		$this->assertSame( '720p', $this->parser->extract_quality( 'Movie.Title.2020.720p.WEB-DL' ) );
	}

	public function test_extract_quality_finds_2160p(): void {
		$this->assertSame( '2160p', $this->parser->extract_quality( 'Movie.Title.2020.2160p.BluRay' ) );
	}

	public function test_extract_quality_finds_4k(): void {
		$this->assertSame( '4k', $this->parser->extract_quality( 'Movie.Title.2020.4K.HDR' ) );
	}

	public function test_extract_quality_returns_null_when_no_match(): void {
		$this->assertNull( $this->parser->extract_quality( 'Movie.Title.2020.FRENCH' ) );
	}

	// -------------------------------------------------------------------------
	// extract_language
	// -------------------------------------------------------------------------

	public function test_extract_language_finds_vff(): void {
		$this->assertSame( 'VFF', $this->parser->extract_language( 'Show.S01E02.VFF.1080p' ) );
	}

	public function test_extract_language_finds_vostfr(): void {
		$this->assertSame( 'VOSTFR', $this->parser->extract_language( 'Show.S01E02.VOSTFR.720p' ) );
	}

	public function test_extract_language_finds_truefrench(): void {
		$this->assertSame( 'TRUEFRENCH', $this->parser->extract_language( 'Movie.Title.2020.TRUEFRENCH.1080p' ) );
	}

	public function test_extract_language_finds_multi(): void {
		$this->assertSame( 'MULTI', $this->parser->extract_language( 'Movie.Title.2020.MULTI.1080p' ) );
	}

	public function test_extract_language_returns_null_when_no_match(): void {
		$this->assertNull( $this->parser->extract_language( 'Movie.Title.2020.1080p.x264' ) );
	}
}
