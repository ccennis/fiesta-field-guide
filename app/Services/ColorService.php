<?php

namespace App\Services;

use App\Models\Color;

class ColorService extends BaseService
{
    /**
     * Swatch values edited here are held in the database only. The catalog
     * import rebuilds colors from database/seed-data/color-hex.csv, so a value
     * that should survive a re-import belongs in that file too.
     */
    public function update(Color $color, array $data): Color
    {
        $color->update($data);

        return $color->fresh('line');
    }
}
