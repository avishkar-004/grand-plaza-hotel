<?php

namespace Tests\Unit\Security;

use PHPUnit\Framework\TestCase;

class XSSPreventionTest extends TestCase
{
    public function test_htmlspecialchars_blocks_script_tags(): void
    {
        $malicious = '<script>alert("xss")</script>';
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $safe);
        $this->assertStringContainsString('&lt;script&gt;', $safe);
    }

    public function test_htmlspecialchars_blocks_event_handlers(): void
    {
        $malicious = '" onmouseover="alert(1)"';
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('"', $safe);
        $this->assertStringContainsString('&quot;', $safe);
    }

    public function test_htmlspecialchars_blocks_img_onerror(): void
    {
        $malicious = '<img src=x onerror="alert(1)">';
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<img', $safe);
        $this->assertStringContainsString('&lt;img', $safe);
    }

    public function test_htmlspecialchars_blocks_javascript_uri(): void
    {
        $malicious = 'javascript:alert(document.cookie)';
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        // htmlspecialchars doesn't block javascript: URIs (no special chars)
        // This test documents the limitation - additional validation is needed
        $this->assertStringContainsString('javascript:', $safe);
    }

    public function test_htmlspecialchars_encodes_ampersand(): void
    {
        $input = 'Tom & Jerry';
        $safe = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString('&amp;', $safe);
    }

    public function test_htmlspecialchars_encodes_single_quotes(): void
    {
        $malicious = "' onclick='alert(1)'";
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString("'", $safe);
        $this->assertStringContainsString('&#039;', $safe);
    }

    public function test_sql_injection_in_htmlspecialchars(): void
    {
        $malicious = "admin' OR '1'='1' --";
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString('&#039;', $safe);
        $this->assertStringNotContainsString("'", $safe);
    }

    public function test_nested_script_tags(): void
    {
        $malicious = '<<script>script>alert("xss")<</script>/script>';
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $safe);
    }

    public function test_svg_xss_vector(): void
    {
        $malicious = '<svg onload="alert(1)">';
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<svg', $safe);
        $this->assertStringContainsString('&lt;svg', $safe);
    }

    public function test_encoded_xss_still_safe_after_double_encoding(): void
    {
        $malicious = '&lt;script&gt;alert(1)&lt;/script&gt;';
        $safe = htmlspecialchars($malicious, ENT_QUOTES, 'UTF-8');

        // Double encoding makes the & into &amp;, so it can't decode back to tags
        $this->assertStringContainsString('&amp;lt;', $safe);
    }

    public function test_empty_string_is_safe(): void
    {
        $safe = htmlspecialchars('', ENT_QUOTES, 'UTF-8');
        $this->assertEquals('', $safe);
    }

    public function test_normal_text_unchanged(): void
    {
        $normal = 'Hello World 123';
        $safe = htmlspecialchars($normal, ENT_QUOTES, 'UTF-8');

        $this->assertEquals($normal, $safe);
    }
}
