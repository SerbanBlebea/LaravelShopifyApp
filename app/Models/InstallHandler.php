<?php

namespace App\Models;

use PHPShopify\Interfaces\ShopifySDKInterface;
use App\Interfaces\InstallInterface;
use App\Models\App;
use App\Models\Asset;

class InstallHandler implements InstallInterface
{
    private $api = null;

    public function __construct(ShopifySDKInterface $api)
    {
        $this->api = $api;
    }

    public function installApp(App $app)
    {
        $assets = $app->assets;
        $theme_id = $this->getMainThemeId();

        $result = true;
        foreach($assets as $asset)
        {
            $response = $this->install($asset, $theme_id);
            if($response["key"] !== $this->getAssetKey($asset))
            {
                $result = false;
            }
        }
        return $result;
    }

    public function install(Asset $asset, String $theme_id)
    {
        if(in_array($asset->asset_type, ["sections", "snippets", "assets"]) == true)
        {
            $file_path = $this->assetPath($asset);

            $payload = [
                "key"   => $this->getAssetKey($asset),
                "value" => file_get_contents($file_path)
            ];

            return $this->api->Theme($theme_id)->Asset->put($payload);
        } else {
            throw new Exception("Asset type not correct", 1);
        }
    }

    private function getMainThemeId()
    {
        $themes = $this->api->Theme->get();

        foreach($themes as $theme)
        {
            if($theme["role"] == "main")
            {
                return $theme["id"];
            }
        }
    }

    // Asset key is composed from asset type and asset name
    private function getAssetKey(Asset $asset)
    {
        return $asset->asset_type . "/" . $this->returnLiquid($asset);
    }

    private function returnLiquid(Asset $asset)
    {
        if($asset->asset_type == "assets")
        {
            return $asset->asset_name;
        } else {
            return (strpos($asset->asset_name, ".liquid") == false) ? $asset->asset_name . ".liquid" : $asset->asset_name;
        }
    }

    public function getAsset(String $theme_id, String $key)
    {
        return $this->api->Theme($theme_id)->Asset->get(["asset[key]" => $key]);
    }

    private function assetPath(Asset $asset)
    {
        return config('app.url') . (($asset->asset_path[0] == "/") ? $asset->asset_path : "/" . $asset->asset_path);
    }

    private function deleteAsset(String $theme_id, String $key)
    {
        return $this->api->Theme($theme_id)->Asset->delete(["asset[key]" => $key]);
    }

    public function uninstallApp(App $app)
    {
        $theme_id = $this->getMainThemeId();
        $assets = $app->assets;

        $result = true;
        foreach($assets as $asset)
        {
            $response = $this->deleteAsset($theme_id, $this->getAssetKey($asset));
            if($response !== [])
            {
                $result = false;
            }
        }
        return $result;
    }
}
