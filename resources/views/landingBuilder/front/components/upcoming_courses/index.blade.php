{{-- CVH: front view for the "Upcoming Courses" landing component (this template version shipped only its admin form and stylesheet). --}}
@if(!empty($landingComponent) and $landingComponent->enable)
    @php
        $contents = [];
        if (!empty($landingComponent->content)) {
            $contents = json_decode($landingComponent->content, true);
        }

        $frontComponentsDataMixins = (new \App\Mixins\LandingBuilder\FrontComponentsDataMixins());
        $upcomingCourses = $frontComponentsDataMixins->getUpcomingCoursesData();
    @endphp

    @if($upcomingCourses->isNotEmpty())
        @push('styles_top')
            <link rel="stylesheet" href="{{ getLandingComponentStylePath("upcoming_courses") }}">
        @endpush

        <div class="container">
            <div class="upcoming-courses-section position-relative" @if(!empty($contents['background'])) style="background-image: url({{ $contents['background'] }})" @endif>
                <div class="d-flex flex-column flex-lg-row align-items-lg-end justify-content-lg-between gap-16">
                    <div class="">
                        @if(!empty($contents['main_content']['pre_title']))
                            <div class="d-inline-flex-center py-8 px-16 rounded-8 border-primary bg-primary-20 font-12 text-primary">{{ $contents['main_content']['pre_title'] }}</div>
                        @endif

                        @if(!empty($contents['main_content']['title']))
                            <h2 class="mt-8 font-32 text-dark">{{ $contents['main_content']['title'] }}</h2>
                        @endif

                        @if(!empty($contents['main_content']['subtitle']))
                            <p class="mt-12 font-16 text-gray-500">{!! nl2br(e($contents['main_content']['subtitle'])) !!}</p>
                        @endif
                    </div>

                    @php
                        $upcomingButton = $contents['main_content']['button'] ?? [];
                        $upcomingButtonLabel = !empty($upcomingButton['label']) ? $upcomingButton['label'] : trans('update.view_more');
                        $upcomingButtonUrl = !empty($upcomingButton['url']) ? $upcomingButton['url'] : '/upcoming_courses?sort=newest';
                    @endphp

                    <a href="{{ $upcomingButtonUrl }}" class="btn-flip-effect btn-flip-effect__no-side d-inline-flex align-items-center font-16 gap-8 font-weight-bold text-primary flex-shrink-0" data-text="{{ $upcomingButtonLabel }}">
                        <span class="btn-flip-effect__text">{{ $upcomingButtonLabel }}</span>
                        <x-iconsax-lin-arrow-right class="icons text-primary" width="24px" height="24px"/>
                    </a>
                </div>

                <div class="row">
                    @include('design_1.web.upcoming_courses.components.cards.grids.index', ['upcomingCourses' => $upcomingCourses, 'gridCardClassName' => "col-12 col-md-6 col-lg-3 mt-24"])
                </div>
            </div>
        </div>
    @endif
@endif
