<p align="center">
    <h1 align="center">TechNimbus Stripe</h1>
    <br>
</p>

<h3>Stripe</h3>
<p>This module is for those who are willing to sell subscriptions products along with simple/virtual products</p>
<p>It also perform 3d secure (when required) and radar risk level</p>

<h3>Instructions</h3>

<ul>
    <li>1. Go to the Magento root directory</li>
    <li>1. Run the command: <code>composer config repositories.kin-allan-stripe git https://github.com/kin-allan/technimbus-stripe</code></li>
    <li>2. Then: <code>composer require kin-allan/technimbus-stripe:1.0.0</code></li>
    <li>3. After the composer process is finished, run those commands:</li>
    <li><code>php bin/magento module:enable TechNimbus_Stripe</code></li>
    <li><code>php bin/magento setup:upgrade</code></li>
    <li><code>php bin/magento setup:di:compile</code></li>
    <li><code>php bin/magento cache:flush</code></li>
</ul>
