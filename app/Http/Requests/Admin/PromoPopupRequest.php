<?php

namespace App\Http\Requests\Admin;

use App\Domain\Promotion\PromoPopupAudience;
use App\Models\PromoPopup;
use App\UseCases\Admin\PromoPopup\Data\PromoPopupInput;
use App\Util\AdminContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PromoPopupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** アプリ内パスか https の URL だけを許可し、javascript: や //別ドメイン を入れさせない */
    private const LINK_PATTERN = '#^(/[^/\\\\]|/$|https://)#';

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $projectId = AdminContext::requireCurrentProjectId();

        return [
            'title' => ['required', 'string', 'max:120'],
            'body' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:2048'],
            'remove_image' => ['nullable', 'boolean'],
            'display_type' => ['required', Rule::in(array_keys(PromoPopup::DISPLAY_TYPES))],
            'button_label' => ['nullable', 'string', 'max:40'],
            'link_url' => ['nullable', 'string', 'max:500', 'regex:'.self::LINK_PATTERN],
            'secondary_button_label' => ['nullable', 'string', 'max:40'],
            'secondary_link_url' => ['nullable', 'string', 'max:500', 'regex:'.self::LINK_PATTERN],
            'frequency' => ['required', Rule::in(array_keys(PromoPopup::FREQUENCIES))],
            'audience' => ['required', Rule::in(array_keys(PromoPopupAudience::getValues()))],
            'audience_tag_ids' => ['nullable', 'array'],
            'audience_tag_ids.*' => ['integer', Rule::exists('member_tags', 'id')->where('project_id', $projectId)],
            'audience_ranks' => ['nullable', 'array'],
            'audience_ranks.*' => ['string', 'max:50'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', Rule::exists('stores', 'id')->where('project_id', $projectId)],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:-999', 'max:999'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        $message = 'リンク先は「/coupon」のようなアプリ内のパスか、https:// から始まるURLを入力してください。';

        return ['link_url.regex' => $message, 'secondary_link_url.regex' => $message];
    }

    public function toInput(): PromoPopupInput
    {
        $audience = (string) $this->validated('audience');
        // 1つも選ばなければ全店舗に出す
        $storeIds = array_values(array_map('intval', (array) $this->validated('store_ids', [])));

        return new PromoPopupInput(
            title: (string) $this->validated('title'),
            body: $this->validated('body'),
            displayType: (string) $this->validated('display_type'),
            buttonLabel: $this->validated('button_label'),
            linkUrl: $this->validated('link_url'),
            secondaryButtonLabel: $this->validated('secondary_button_label'),
            secondaryLinkUrl: $this->validated('secondary_link_url'),
            frequency: (string) $this->validated('frequency'),
            audience: $audience,
            audienceTagIds: $audience === PromoPopupAudience::SEGMENT ? array_values(array_map('intval', (array) $this->validated('audience_tag_ids', []))) : [],
            audienceRanks: $audience === PromoPopupAudience::SEGMENT ? array_values(array_map('strval', (array) $this->validated('audience_ranks', []))) : [],
            storeIds: $storeIds === [] ? null : $storeIds,
            isActive: $this->boolean('is_active'),
            sortOrder: (int) $this->validated('sort_order', 0),
            startsAt: $this->validated('starts_at'),
            endsAt: $this->validated('ends_at'),
            image: $this->file('image'),
            removeImage: $this->boolean('remove_image'),
        );
    }
}
