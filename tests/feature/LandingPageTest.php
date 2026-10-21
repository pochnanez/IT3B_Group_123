<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

final class LandingPageTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHomepageLoadsWithNoOperationalDatabaseTables(): void
    {
        // The starter DB only contains test fixtures; no catalog/booking tables.
        $this->assertFalse(db_connect()->tableExists('products'));
        $this->assertFalse(db_connect()->tableExists('services'));
        $result = $this->get('/');
        $result->assertOK();
        $result->assertSee('Online service booking is not available yet.');
        $body = (string) $result->response()->getBody();
        $this->assertStringContainsString(site_url('login'), $body);
        $this->assertStringContainsString(site_url('register'), $body);
        $this->assertStringContainsString('Specific models, prices and stock availability are not yet published.', $body);
        $this->assertStringNotContainsString('Welcome to CodeIgniter', $body);
    }

    public function testEmptyCategoryGuidesHaveReadableFallbacks(): void
    {
        $site = config('PublicSite');
        $originalProducts = $site->products;
        $originalServices = $site->services;
        try {
            $site->products = [];
            $site->services = [];
            $result = $this->get('/');
            $result->assertOK();
            $result->assertSee('Product category information is being updated.');
            $result->assertSee('Service information is being updated.');
        } finally {
            $site->products = $originalProducts;
            $site->services = $originalServices;
        }
    }

    public function testCategoryContentIsEscaped(): void
    {
        $site = config('PublicSite');
        $original = $site->products;
        try {
            $site->products[0]['name'] = '<script>alert("unsafe")</script>';
            $body = (string) $this->get('/')->response()->getBody();
            $this->assertStringNotContainsString('<script>alert("unsafe")</script>', $body);
            $this->assertStringContainsString('&lt;script&gt;', $body);
        } finally {
            $site->products = $original;
        }
    }
}
