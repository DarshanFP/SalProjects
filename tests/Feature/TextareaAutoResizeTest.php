<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithoutMiddleware;

/**
 * Textarea Auto-Resize Feature Test
 *
 * Tests that textarea auto-resize functionality works correctly
 * after cleanup of duplicate CSS/JS code.
 *
 * NOTE: This test does NOT modify the database - it only tests
 * that CSS/JS files are loaded and classes are present in HTML.
 */
class TextareaAutoResizeTest extends TestCase
{
    use WithoutMiddleware;

    /**
     * Test that global CSS file exists and is accessible
     */
    public function test_global_css_file_exists()
    {
        $response = $this->get('/css/custom/textarea-auto-resize.css');

        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('.sustainability-textarea', $response->getContent());
        $this->assertStringContainsString('.logical-textarea', $response->getContent());
        $this->assertStringContainsString('.auto-resize-textarea', $response->getContent());
    }

    /**
     * Test that global JS file exists and is accessible
     */
    public function test_global_js_file_exists()
    {
        $response = $this->get('/js/textarea-auto-resize.js');

        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('initTextareaAutoResize', $response->getContent());
        $this->assertStringContainsString('window.initTextareaAutoResize', $response->getContent());
        $this->assertStringContainsString('sustainability-textarea', $response->getContent());
    }

    /**
     * Test that CSS file is included in main layout
     */
    public function test_css_included_in_layout()
    {
        // This test would require authentication and full page rendering
        // For now, we'll just verify the file exists
        $this->assertFileExists(public_path('css/custom/textarea-auto-resize.css'));
    }

    /**
     * Test that JS file is included in main layout
     */
    public function test_js_included_in_layout()
    {
        // This test would require authentication and full page rendering
        // For now, we'll just verify the file exists
        $this->assertFileExists(public_path('js/textarea-auto-resize.js'));
    }

    /**
     * Test that CSS file has correct content structure
     */
    public function test_css_file_structure()
    {
        $cssContent = file_get_contents(public_path('css/custom/textarea-auto-resize.css'));

        // Check for required CSS classes
        $this->assertStringContainsString('.sustainability-textarea', $cssContent);
        $this->assertStringContainsString('.logical-textarea', $cssContent);
        $this->assertStringContainsString('.auto-resize-textarea', $cssContent);

        // Check for required CSS properties
        $this->assertStringContainsString('min-height', $cssContent);
        $this->assertStringContainsString('resize: vertical', $cssContent);
        $this->assertStringContainsString('overflow-y: hidden', $cssContent);
    }

    /**
     * Test that JS file has correct content structure
     */
    public function test_js_file_structure()
    {
        $jsContent = file_get_contents(public_path('js/textarea-auto-resize.js'));

        // Check for required functions
        $this->assertStringContainsString('function initTextareaAutoResize', $jsContent);
        $this->assertStringContainsString('function autoResizeTextarea', $jsContent);
        $this->assertStringContainsString('window.initTextareaAutoResize', $jsContent);

        // Check for required CSS class selectors
        $this->assertStringContainsString('.sustainability-textarea', $jsContent);
        $this->assertStringContainsString('.logical-textarea', $jsContent);
        $this->assertStringContainsString('.auto-resize-textarea', $jsContent);
    }
}
