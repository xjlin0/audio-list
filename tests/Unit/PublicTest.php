<?php

namespace AudioList\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Audio_List_Public;

class PublicTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        // Global WP mocks for shortcode-related tests
        Functions\expect('add_shortcode')->zeroOrMoreTimes();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function test_youtube_url_detection() {
        $public = new Audio_List_Public('audio-list', '1.0.0');
        
        $reflect = new \ReflectionClass(Audio_List_Public::class);
        $method = $reflect->getMethod('is_youtube_url');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($public, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertTrue($method->invoke($public, 'https://youtu.be/dQw4w9WgXcQ'));
        $this->assertFalse($method->invoke($public, 'https://google.com'));
    }

    public function test_get_youtube_id() {
        $public = new Audio_List_Public('audio-list', '1.0.0');
        
        $reflect = new \ReflectionClass(Audio_List_Public::class);
        $method = $reflect->getMethod('get_youtube_id');
        $method->setAccessible(true);

        $this->assertEquals('dQw4w9WgXcQ', $method->invoke($public, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
        $this->assertEquals('dQw4w9WgXcQ', $method->invoke($public, 'https://youtu.be/dQw4w9WgXcQ'));
        $this->assertEquals('12345678901', $method->invoke($public, 'https://www.youtube.com/live/12345678901?feature=share'));
    }

    public function test_display_audio_list_with_no_results() {
        global $wpdb;
        $wpdb = Mockery::mock('\stdClass');
        $wpdb->prefix = 'wp_';
        
        // Mock WP functions
        Functions\expect('shortcode_atts')->andReturnFirstArg();
        Functions\expect('sanitize_text_field')->andReturnFirstArg();
        Functions\expect('esc_url')->andReturnFirstArg();
        Functions\expect('error_log')->zeroOrMoreTimes();
        
        $wpdb->shouldReceive('prepare')->andReturn('MOCKED QUERY');
        $wpdb->shouldReceive('get_results')->with('MOCKED QUERY')->andReturn([]);

        $public = new Audio_List_Public('audio-list', '1.0.0');
        $output = $public->display_audio_list([]);

        $this->assertStringContainsString('No audio available', $output);
    }

    public function test_display_audio_list_renders_items() {
        global $wpdb;
        $wpdb = Mockery::mock('\stdClass');
        $wpdb->prefix = 'wp_';
        
        Functions\expect('shortcode_atts')->andReturnUsing(function($defaults, $atts) {
            return array_merge($defaults, $atts);
        });
        Functions\expect('sanitize_text_field')->andReturnFirstArg();
        Functions\expect('esc_url')->andReturnFirstArg();
        Functions\expect('esc_html')->andReturnFirstArg();

        $mock_result = (object)[
            'id' => 1,
            'sermondate' => '2026-06-05',
            'type' => '主日崇拜',
            'section' => 'John 3:16',
            'series' => 'Love',
            'audiofile' => 'test.mp3',
            'note' => 'Test note',
            'topic' => 'Test Topic',
            'speaker' => 'Test Speaker',
            'link' => 'https://example.com/handout.pdf',
            'url' => 'https://youtube.com/watch?v=123'
        ];

        $wpdb->shouldReceive('prepare')->andReturn('MOCKED QUERY');
        $wpdb->shouldReceive('get_results')->andReturn([$mock_result]);

        $public = new Audio_List_Public('audio-list', '1.0.0');
        $output = $public->display_audio_list(['url' => 'https://s3.com/']);

        $this->assertStringContainsString('2026-06-05', $output);
        $this->assertStringContainsString('Test Topic', $output);
        $this->assertStringContainsString('test.mp3', $output);
        $this->assertStringContainsString('handout-link', $output);
        $this->assertStringContainsString('youtube-item', $output);
    }
}
