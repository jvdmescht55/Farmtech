<?php

namespace App\Services;

use App\Models\Product;

/**
 * Builds the bilingual (English + Simplified Chinese) supplier inquiry text
 * and the 1-click wa.me link that sends it. One fixed template — this is a
 * standard raw-asset request sent identically to every supplier, not
 * per-product marketing copy.
 */
class SupplierOutreachMessageBuilder
{
    public function __construct(
        private readonly SupplierContactExtractor $contactExtractor = new SupplierContactExtractor(),
    ) {}

    public function message(Product $product): string
    {
        $supplierName = $this->supplierDisplayName($product);

        $english = "Hello {$supplierName} team. We are finalizing bulk procurement for South Africa for: "
            ."{$product->title}. Please share raw, unbranded MP4 field-testing videos (IP68 waterproof tests "
            ."/ live operation), app screen recordings, and wiring schematics via Google Drive or file "
            .'attachment. Thank you.';

        $chinese = "您好，{$supplierName}团队。我们正在为南非市场落实批量采购：{$product->title}。"
            .'请通过谷歌网盘（Google Drive）或文件附件分享未加水印的原始MP4实地测试视频'
            .'（IP68防水测试/实机操作演示）、应用程序录屏，以及接线原理图。谢谢。';

        return $english."\n\n".$chinese;
    }

    /**
     * null when no supplier phone/WhatsApp number is on file — a manually
     * entered `supplier_phone` (see SupplierContactExtractor::resolve()) or
     * one auto-found in scraped data. Never a fabricated one.
     */
    public function whatsAppUrl(Product $product): ?string
    {
        $phone = $this->contactExtractor->resolve($product);

        if (! $phone) {
            return null;
        }

        // wa.me wants digits only, no leading '+'.
        $dialable = ltrim($phone, '+');

        return "https://wa.me/{$dialable}?text=".rawurlencode($this->message($product));
    }

    /**
     * A narrower bilingual ask — high-resolution product demo / field-test
     * videos specifically — for the admin product page's "Request High-Res
     * Demo Videos" quick action. Same fixed-template philosophy as message().
     */
    public function demoVideoMessage(Product $product): string
    {
        $supplierName = $this->supplierDisplayName($product);

        $english = "Hello {$supplierName} team. For our South African online store we would like to feature: "
            ."{$product->title}. Could you send high-resolution (1080p or 4K) demo and field-test videos — "
            .'unbranded MP4, no watermark — plus any product photos not on the listing? A Google Drive or '
            .'Dropbox link is perfect. Thank you.';

        $chinese = "您好，{$supplierName}团队。我们的南非线上商店计划展示：{$product->title}。"
            .'烦请提供高清（1080P或4K）产品演示及实地测试视频（无水印、无品牌标识的原始MP4），'
            .'以及listing之外的产品照片。请通过谷歌网盘（Google Drive）或Dropbox链接发送。谢谢。';

        return $english."\n\n".$chinese;
    }

    /**
     * 1-click wa.me link carrying demoVideoMessage(). null when no WhatsApp
     * number is on file (same resolution as whatsAppUrl()).
     */
    public function demoVideoWhatsAppUrl(Product $product): ?string
    {
        $phone = $this->contactExtractor->resolve($product);

        if (! $phone) {
            return null;
        }

        return 'https://wa.me/'.ltrim($phone, '+').'?text='.rawurlencode($this->demoVideoMessage($product));
    }

    /**
     * A full bilingual sourcing brief for a first-contact inquiry on a
     * research-candidate product (see Top20BoerTechSeeder) — the standard
     * asks (OEM unwatermarked demo-video drive link, app/SDK + API docs,
     * sample DDP air rate to OR Tambo, tiered unit price, MOQ, lead time)
     * plus one clause tailored to the product's category. Category is the
     * only per-product variable; the skeleton is fixed.
     */
    public function sourcingBriefMessage(Product $product): string
    {
        $title = $product->title;
        [$enClause, $zhClause] = $this->categoryClause($product);

        $english = "Hello, we are Farmtech, an agri-equipment importer for the South African market, evaluating suppliers for:\n"
            ."  {$title}\n\n"
            ."Please send:\n"
            ."1. An OEM demo / field-test video pack — unbranded, no watermark, MP4 — via Google Drive or Dropbox link.\n"
            ."2. App / SDK / API documentation and confirmation of Android + iOS support where applicable.\n"
            ."3. A sample DDP air-freight quote to OR Tambo (JNB), Johannesburg, for 1 sample + a 50-unit trial order.\n"
            ."4. Unit price at 1 / 10 / 50 / 100 pcs, MOQ, and production lead time.\n"
            ."5. Datasheet, certifications, and warranty terms.\n"
            .$enClause."\n\n"
            .'Thank you — please reply in English or 中文.';

        $chinese = "您好，我们是 Farmtech，面向南非市场的农业设备进口商，正在为以下产品评估供应商：\n"
            ."  {$title}\n\n"
            ."烦请提供：\n"
            ."1. OEM 演示 / 实地测试视频包——无品牌标识、无水印、MP4 格式——通过谷歌网盘（Google Drive）或 Dropbox 链接发送。\n"
            ."2. App / SDK / API 文档，并确认是否支持 Android 与 iOS（如适用）。\n"
            ."3. 至南非约翰内斯堡 OR Tambo 机场（JNB）的 DDP 空运报价样单：1 台样品 + 50 台试单。\n"
            ."4. 1 / 10 / 50 / 100 台的单价、最小起订量（MOQ）及生产交期。\n"
            ."5. 规格书、认证证书及保修条款。\n"
            .$zhClause."\n\n"
            .'谢谢，回复中英文皆可。';

        return $english."\n\n---\n\n".$chinese;
    }

    /**
     * One extra, category-specific ask. @return array{0: string, 1: string} [english, chinese]
     */
    private function categoryClause(Product $product): array
    {
        return match ($product->category->value) {
            'fuel_monitoring', 'smart_irrigation', 'fleet_trackers', 'vehicle_accessories' => [
                '6. Confirm the cellular module supports 4G LTE bands B1 / B3 / B8 / B20 / B28 (South Africa), and state the RF link / frequency for any wireless sensor.',
                '6. 请确认蜂窝模块支持 4G LTE 频段 B1 / B3 / B8 / B20 / B28（南非），并说明无线传感器的射频链路 / 频率。',
            ],
            'fencing' => [
                '6. State joule rating (stored vs output), and provide any NRCS / SANS 60335-2-76 test report for the energizer; for alarm units confirm the 4G bands (B1/B3/B8/B20/B28).',
                '6. 请说明焦耳值（储能与输出），并提供电围栏主机的 NRCS / SANS 60335-2-76 测试报告；报警型号请确认 4G 频段（B1/B3/B8/B20/B28）。',
            ],
            'solar_pumps' => [
                '6. Provide the pump head / flow performance curve and the controller motor-protection spec (dry-run, over-current, phase loss).',
                '6. 请提供水泵扬程 / 流量性能曲线，以及控制器的电机保护规格（防空转、过流、缺相）。',
            ],
            'ultrasound' => [
                '6. Confirm the rectal linear probe frequency and scan depth, and include clinical demo footage for both cattle and sheep.',
                '6. 请确认直肠线阵探头的频率与扫查深度，并附牛与羊的临床演示视频。',
            ],
            'scales', 'platform_scales' => [
                '6. Provide the load-cell datasheet, OIML / verification accuracy class, and the indicator comms protocol (Bluetooth / RS232 / RS485).',
                '6. 请提供称重传感器规格书、OIML / 检定精度等级，以及仪表通讯协议（蓝牙 / RS232 / RS485）。',
            ],
            'moisture_meters', 'thermal_diagnostics' => [
                '6. Provide the calibration certificate, the accuracy / repeatability spec, and the sensor / detector datasheet.',
                '6. 请提供校准证书、精度 / 重复性规格，以及传感器 / 探测器规格书。',
            ],
            'rfid', 'industrial_rfid' => [
                '6. Confirm ISO 11784/11785 (134.2 kHz) HDX + FDX-B support, stated read range on a standard ISO ear tag, and the Bluetooth / API integration for weigh indicators.',
                '6. 请确认支持 ISO 11784/11785（134.2 kHz）HDX 与 FDX-B、标准 ISO 耳标的读取距离，以及与称重仪表对接的蓝牙 / API 方案。',
            ],
            default => [
                '6. Provide the full datasheet, materials of construction, and IP rating with the supporting test report.',
                '6. 请提供完整规格书、结构材料，以及带测试报告的 IP 防护等级。',
            ],
        };
    }

    /**
     * Fallback contact route for the majority of listings with no phone
     * number on file: the product's own Alibaba listing/inquiry page, so the
     * same bilingual message (see message()) can be pasted into
     * Alibaba's TradeManager chat instead of WhatsApp. Real data only —
     * `source_url` is the actual scraped listing URL, never guessed.
     */
    public function alibabaChatUrl(Product $product): ?string
    {
        $url = trim((string) $product->source_url);

        return $url !== '' ? $url : null;
    }

    private function supplierDisplayName(Product $product): string
    {
        $name = trim((string) $product->supplier_name);

        return $name !== '' ? $name : 'Supplier';
    }
}
