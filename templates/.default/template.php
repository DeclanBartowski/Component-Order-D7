<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var array $arParams */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var array $arResult */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
?>
<form id="tq_payment">
    <div class="form_body">
        <?
        foreach ($arResult['PARAMS'] as $key => $item) {
            ?>
            <div class="form-control">
                <label for="<?= $key ?>">
                        <span>
                            <?= $item['TITLE'] ?>
                            <? if ($item['REQUIRED']): ?>
                                <span class="star">*</span>
                            <?endif; ?>
                        </span>
                </label>
                <input type="<?= $item['TYPE'] ?>" class="inputtext" id="<?= $key ?>"
                       <? if ($item['REQUIRED']): ?>required=""<?endif; ?> name="<?= $key ?>"
                       value="<?= $item['VALUE'] ?>" aria-required="true" aria-invalid="false">
            </div>
            <?
        }
        ?>
        <div class="clearboth"></div>
    </div>
    <div class="error"></div>
    <div class="form_footer">
        <input type="submit" class="btn btn-default" value="<?= \Bitrix\Main\Localization\Loc::getMessage('PAYMENT') ?>"
               name="web_form_submit">
    </div>
</form>
<div id="redirectLink">
</div>