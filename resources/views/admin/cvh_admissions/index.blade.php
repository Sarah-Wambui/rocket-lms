{{-- CVH Africa: Admin → Users → Admissions (settings, applicants, approve/reject, offline payment, CSV export) --}}
@extends('admin.layouts.app')

@section('content')
    <section class="section">
        <div class="section-header">
            <h1>{{ $pageTitle }}</h1>
            <div class="section-header-breadcrumb">
                <div class="breadcrumb-item"><a href="{{ getAdminPanelUrl() }}">{{ trans('admin/main.dashboard') }}</a></div>
                <div class="breadcrumb-item active">{{ $pageTitle }}</div>
            </div>
        </div>

        <div class="section-body">
            @if(session('cvh_admission_admin_status'))
                <div class="alert alert-success">{{ session('cvh_admission_admin_status') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="row">
                @foreach(['under_review' => 'Waiting for review', 'applying' => 'Still applying', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
                    <div class="col-6 col-md-3">
                        <div class="card card-statistic-1"><div class="card-wrap p-16">
                            <div class="font-12 text-gray-500">{{ $label }}</div>
                            <div class="font-24 font-weight-bold">{{ $counts[$key] ?? 0 }}</div>
                        </div></div>
                    </div>
                @endforeach
            </div>

            {{-- Settings --}}
            @can('admin_users_edit')
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h5 class="font-14 mb-0">Admission settings</h5>
                            <p class="font-12 mt-4 mb-0 text-gray-500">When admission is on, new learners must complete these steps and be approved before they can enrol in courses. Learners who registered before you switch it on are not affected.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="{{ getAdminPanelUrl('/cvh-admissions/settings') }}" method="post">
                            {{ csrf_field() }}
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" name="enabled" class="custom-control-input" id="admissionEnabled" {{ $settings['enabled'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="admissionEnabled">Admission is on</label>
                                        </div>
                                        @if($settings['enabled'] and $settings['enabled_at'])
                                            <div class="text-muted text-small mt-1">On since {{ dateTimeFormat($settings['enabled_at'], 'j M Y H:i') }}; learners registered after that must apply.</div>
                                        @endif
                                    </div>

                                    <div class="form-group">
                                        <label class="input-label">Application form</label>
                                        <select name="form_id" class="form-control">
                                            <option value="">No application form</option>
                                            @foreach($forms as $formItem)
                                                <option value="{{ $formItem->id }}" {{ (string)$settings['form_id'] === (string)$formItem->id ? 'selected' : '' }}>{{ $formItem->title }} (/forms/{{ $formItem->url }}){{ $formItem->enable ? '' : ' (disabled)' }}</option>
                                            @endforeach
                                        </select>
                                        <div class="text-muted text-small mt-1">Create it in <a href="{{ getAdminPanelUrl('/forms') }}">Forms</a>. Switch on "Enable login" so answers are linked to the learner, and make sure the form is enabled.</div>
                                    </div>

                                    <div class="form-group">
                                        <label class="input-label">Admission fee ({{ currencySign($currency) }})</label>
                                        <input type="number" step="0.01" min="0" name="fee" value="{{ $settings['fee'] }}" class="form-control">
                                        <div class="text-muted text-small mt-1">0 = no fee. Paid online through Paystack (Payment Channels), or record a payment below.</div>
                                    </div>

                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" name="auto_approve" class="custom-control-input" id="admissionAutoApprove" {{ $settings['auto_approve'] ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="admissionAutoApprove">Approve automatically once the form and fee are done</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label class="input-label">Instructions shown to applicants (optional)</label>
                                        <textarea name="instructions" rows="7" class="form-control">{{ $settings['instructions'] }}</textarea>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save settings</button>
                        </form>
                    </div>
                </div>
            @endcan

            {{-- Applicants --}}
            <div class="card">
                <div class="card-header justify-content-between">
                    <h5 class="font-14 mb-0">Applicants</h5>
                    <a href="{{ getAdminPanelUrl('/cvh-admissions/export') . '?' . http_build_query(request()->only('status', 'search')) }}" class="btn btn-outline-primary btn-sm">Export CSV</a>
                </div>
                <div class="card-body">
                    <form method="get" class="d-flex flex-wrap gap-8 mb-16">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control mr-2" style="max-width: 280px;" placeholder="Name, email or payment reference">
                        <select name="status" class="form-control mr-2" style="max-width: 200px;">
                            <option value="">All statuses</option>
                            @foreach($statuses as $key => $label)
                                <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">{{ trans('admin/main.filter') }}</button>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped font-14">
                            <tr>
                                <th class="text-left">Applicant</th>
                                <th>Registered</th>
                                <th>Application form</th>
                                <th>Admission fee</th>
                                <th>Status</th>
                                <th>{{ trans('admin/main.actions') }}</th>
                            </tr>
                            @forelse($admissions as $admission)
                                <tr>
                                    <td class="text-left">
                                        <div class="font-weight-bold">{{ $admission->full_name ?: '(no name)' }}</div>
                                        <div class="text-muted font-12">{{ $admission->email }}{{ $admission->mobile ? ' · ' . $admission->mobile : '' }}</div>
                                    </td>
                                    <td>{{ dateTimeFormat($admission->user_created_at, 'j M Y') }}</td>
                                    <td>
                                        @if($admission->form_submission_id)
                                            <a href="{{ getAdminPanelUrl("/forms/submissions/{$admission->form_submission_id}/show") }}" target="_blank">View answers</a>
                                        @elseif($settings['form_id'])
                                            <span class="text-muted">Not submitted</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($admission->payment_status === 'paid')
                                            <span class="text-success">Paid</span>
                                            <div class="text-muted font-12">{{ $admission->fee_currency }} {{ number_format((float)$admission->fee_amount, 2) }} · {{ $admission->payment_method === 'manual' ? 'recorded by admin' : 'Paystack' }}</div>
                                            <div class="text-muted font-12">{{ $admission->payment_reference }}</div>
                                        @elseif($admission->payment_status === 'waived')
                                            <span class="text-muted">No fee</span>
                                        @else
                                            <span class="text-warning">Unpaid</span>
                                            @can('admin_users_edit')
                                                <form action="{{ getAdminPanelUrl("/cvh-admissions/{$admission->id}/mark-paid") }}" method="post" class="d-flex mt-1">
                                                    {{ csrf_field() }}
                                                    <input type="text" name="reference" class="form-control form-control-sm mr-1" style="max-width: 150px;" placeholder="Receipt / M-PESA code" required>
                                                    <button type="submit" class="btn btn-sm btn-outline-primary">Mark paid</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                    <td>
                                        @php $badge = ['applying' => 'secondary', 'under_review' => 'warning', 'approved' => 'success', 'rejected' => 'danger'][$admission->status] ?? 'secondary'; @endphp
                                        <span class="badge badge-{{ $badge }}">{{ $statuses[$admission->status] ?? $admission->status }}</span>
                                        @if($admission->admin_note)
                                            <div class="text-muted font-12 mt-1">{{ $admission->admin_note }}</div>
                                        @endif
                                    </td>
                                    <td style="min-width: 220px;">
                                        @can('admin_users_edit')
                                            @if($admission->status !== 'approved')
                                                <form action="{{ getAdminPanelUrl("/cvh-admissions/{$admission->id}/approve") }}" method="post" class="d-inline">
                                                    {{ csrf_field() }}
                                                    <button type="submit" class="btn btn-sm btn-success mb-1" onclick="return confirm('Approve this applicant?')">Approve</button>
                                                </form>
                                            @endif
                                            @if($admission->status !== 'rejected')
                                                <form action="{{ getAdminPanelUrl("/cvh-admissions/{$admission->id}/reject") }}" method="post" class="d-inline" onsubmit="var n = prompt('Reason (optional, shown to the applicant):', ''); if (n === null) return false; this.note.value = n; return true;">
                                                    {{ csrf_field() }}
                                                    <input type="hidden" name="note" value="">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger mb-1">Reject</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-30">No applicants yet.</td></tr>
                            @endforelse
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    {{ $admissions->links() }}
                    <div class="text-muted font-12 mt-2">Admission fees collected: {{ currencySign($currency) }}{{ number_format((float)$feesCollected, 2) }}</div>
                </div>
            </div>
        </div>
    </section>
@endsection
