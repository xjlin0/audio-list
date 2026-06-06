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
            'url' => 'https://youtube.com/watch?v=dQw4w9WgXcQ'
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

    public function test_inject_audio_search_results() {
        global $wpdb;
        $wpdb = Mockery::mock('\stdClass');
        $wpdb->prefix = 'wp_';

        // Mock WP functions
        Functions\expect('get_search_query')->andReturn('Jesus');
        Functions\expect('site_url')->andReturn('https://example.com');
        Functions\expect('current_time')->andReturn('2026-06-05 12:00:00');
        Functions\expect('esc_url')->andReturnFirstArg();
        Functions\expect('esc_html')->andReturnFirstArg();
        Functions\expect('is_admin')->andReturn(false);

        $query_mock = Mockery::mock('\WP_Query');
        $query_mock->shouldReceive('is_main_query')->andReturn(true);
        $query_mock->shouldReceive('is_search')->andReturn(true);

        $mock_result = (object)[
            'sermondate' => '2026-06-05',
            'type' => 'Sermon',
            'section' => 'John 3:16',
            'series' => 'Gospel',
            'audiofile' => '20260605.mp3',
            'topic' => 'Salvation',
            'speaker' => 'Pastor'
        ];

        $wpdb->shouldReceive('esc_like')->andReturn('Jesus');
        $wpdb->shouldReceive('prepare')->andReturn('MOCKED SEARCH QUERY');
        $wpdb->shouldReceive('get_results')->andReturn([$mock_result]);

        $public = new Audio_List_Public('audio-list', '1.0.0');
        $initial_posts = [ (object)['ID' => 10, 'post_title' => 'Real Post'] ];

        $modified_posts = $public->inject_audio_search_results($initial_posts, $query_mock);

        $this->assertCount(2, $modified_posts);
        $this->assertEquals('📂 錄音搜尋結果：找到 1 筆相符資料', $modified_posts[0]->post_title);
        $this->assertStringContainsString('Salvation', $modified_posts[0]->post_content);
        $this->assertStringContainsString('/sermon-2026/#20260605', $modified_posts[0]->post_content);
    }
}
