<?php
$currentYear = date("Y");
$currentMonth = date("m");
?>
<div class="wrap">
    <div id="lkn-recurrency-loading">
        <img
            src="<?php echo LKN_RECURRENCY_GIVE_URL . 'Includes/assets/gifs/loading.gif';?>"
            alt="<?php _e('loading animation', 'lkn-recurrency-give'); ?>"
        >
    </div>
    <header class="lkn-header">
        <a
            href="https://www.linknacional.com.br/"
            title="<?php _e('Link Nacional Page', 'lkn-recurrency-give'); ?>"
            class="lkn-logo-container"
            target="_blank"
        >
            <div class="lkn-image-logo-div">
                <img
                    class="lkn-image-logo"
                    src="<?php echo LKN_RECURRENCY_GIVE_URL . 'Includes/assets/images/link-logo.png';?>"
                    alt="<?php _e('Link Nacional Logo', 'lkn-recurrency-give'); ?>"
                >
            </div>

            <div class="lkn-text-logo-div">
                <p class="lkn-title"><?php _e('Link Nacional', 'lkn-recurrency-give'); ?></p>
                <p class="lkn-sub-title"><?php _e('Lkn Recurrency Give', 'lkn-recurrency-give'); ?></p>
            </div>
        </a>
        <div class="lkn-visit">
            <a
                href="https://www.linknacional.com.br/"
                title="<?php _e('Link Nacional Page', 'lkn-recurrency-give'); ?>"
                target="_blank"
            >
                <img
                    src="<?php echo LKN_RECURRENCY_GIVE_URL . 'Includes/assets/icons/internet.svg';?>"
                    alt="<?php _e('Internet icon', 'lkn-recurrency-give'); ?>"
                >
                <span><?php _e('VISIT US', 'lkn-recurrency-give'); ?></span>
            </a>
        </div>
    </header>
    <h1></h1>
    <div id="lkn-data-container">
        <div id="lkn-select-container">
            <div class="lkn-title-card">
                <h3><?php _e('Select date', 'lkn-recurrency-give'); ?></h3>
            </div>

            <div class="lkn-select-input">
                <label for="month-select"><?php _e('Select month:', 'lkn-recurrency-give'); ?></label>
                <select id="month-select">
                    <option value="01" <?php if ($currentMonth === '01') {
                        echo 'selected';
                    }?> ><?php _e('January', 'lkn-recurrency-give'); ?></option>
                    <option value="02" <?php if ($currentMonth === '02') {
                        echo 'selected';
                    }?> ><?php _e('February', 'lkn-recurrency-give'); ?></option>
                    <option value="03" <?php if ($currentMonth === '03') {
                        echo 'selected';
                    }?> ><?php _e('March', 'lkn-recurrency-give'); ?></option>
                    <option value="04" <?php if ($currentMonth === '04') {
                        echo 'selected';
                    }?> ><?php _e('April', 'lkn-recurrency-give'); ?></option>
                    <option value="05" <?php if ($currentMonth === '05') {
                        echo 'selected';
                    }?> ><?php _e('May', 'lkn-recurrency-give'); ?></option>
                    <option value="06" <?php if ($currentMonth === '06') {
                        echo 'selected';
                    }?> ><?php _e('June', 'lkn-recurrency-give'); ?></option>
                    <option value="07" <?php if ($currentMonth === '07') {
                        echo 'selected';
                    }?> ><?php _e('July', 'lkn-recurrency-give'); ?></option>
                    <option value="08" <?php if ($currentMonth === '08') {
                        echo 'selected';
                    }?> ><?php _e('August', 'lkn-recurrency-give'); ?></option>
                    <option value="09" <?php if ($currentMonth === '09') {
                        echo 'selected';
                    }?> ><?php _e('September', 'lkn-recurrency-give'); ?></option>
                    <option value="10" <?php if ($currentMonth === '10') {
                        echo 'selected';
                    }?> ><?php _e('October', 'lkn-recurrency-give'); ?></option>
                    <option value="11" <?php if ($currentMonth === '11') {
                        echo 'selected';
                    }?> ><?php _e('November', 'lkn-recurrency-give'); ?></option>
                    <option value="12" <?php if ($currentMonth === '12') {
                        echo 'selected';
                    }?> ><?php _e('December', 'lkn-recurrency-give'); ?></option>
                </select>
            </div>

            <div class="lkn-select-input">
                <label for="year-select"><?php _e('Select year:', 'lkn-recurrency-give'); ?></label>
                <select id="year-select">
                    <?php for ($year = 2020; $year <= 2030; $year++) :?>
                    <option value="<?php echo $year; ?>" <?php if ($year == $currentYear) {
                        echo 'selected';
                    } ?>>
                        <?php echo $year;?>
                    </option>
                    <?php endfor;?>
                </select>
            </div>

            <div class="lkn-select-input">
                <label for="currency-select"><?php _e('Select currency:', 'lkn-recurrency-give'); ?></label>
                <select id="currency-select">
                    <option value="BRL">BRL</option>
                </select>
            </div>

            <div class="lkn-select-input">
                <label for="mode-select"><?php _e('Select payment mode:', 'lkn-recurrency-give'); ?></label>
                <select id="mode-select">
                    <option value="test"><?php _e('Test', 'lkn-recurrency-give'); ?></option>
                    <option value="production"><?php _e('Production', 'lkn-recurrency-give'); ?></option>
                </select>
            </div>
        </div>

        <div id="lkn-cust-total">
            <div class="lkn-title-card">
                <h3><?php _e('Total amount (Monthly)', 'lkn-recurrency-give'); ?></h3>
                <button
                    title="<?php _e('General Review', 'lkn-recurrency-give'); ?>"
                    id="lkn-review-button"
                >
                    <img
                        src="<?php echo LKN_RECURRENCY_GIVE_URL . 'Includes/assets/icons/review.svg';?>"
                        alt="<?php _e('Review icon', 'lkn-recurrency-give'); ?>"
                    >
                </button>
            </div>
            <div id="lkn-value"><span>R$ 0.00</span></div>
        </div>
    </div>

    <div class="chart-container">
        <span
            id="lkn-error-message"
            style="display: none;"
        ></span>
        <canvas id="recurrencyChart"></canvas>
    </div>

    <div
        id="lkn-review-modal"
        class="lkn-modal"
        style="display: none;"
    >
        <div class="lkn-modal-container">
            <span
                class="lkn-close-button"
                id="lkn-close-review-modal"
            >&times;</span>
            <h2><?php _e('General Review', 'lkn-recurrency-give'); ?></h2>
            <div id="lkn-modal-content"></div>
        </div>
    </div>
</div>