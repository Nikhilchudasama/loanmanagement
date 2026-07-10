<?php

namespace App\Domains\Borrower\Services;

use App\Domains\Borrower\Models\Borrower;
use App\Domains\Borrower\Resources\BorrowerResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BorrowerService
{
    public function list(int $perPage = 15): array
    {
        return BorrowerResource::collection(
            Borrower::paginate($perPage)
        )->response()->getData(true);
    }

    public function create(array $data): Borrower
    {
        $tenantId = auth()->user()->tenant_id;

        if (! $tenantId) {
            throw new HttpException(403, 'Super admins cannot create borrowers directly.');
        }

        $data['tenant_id'] = $tenantId;

        return Borrower::create($data);
    }

    public function update(Borrower $borrower, array $data): Borrower
    {
        $borrower->update($data);

        return $borrower;
    }

    public function delete(Borrower $borrower): void
    {
        $borrower->delete();
    }
}
