import type { JSX } from 'react';
import React, { useEffect, useRef, useState } from 'react';
import { baseStyleObject, mergeStyles } from './blockStyles';
import type { BuilderBlockData } from './types';

type SliderBlockProps = {
    block: BuilderBlockData;
};

type Slide = {
    image_url?: string;
    caption?: string;
    title?: string;
};

function SectionHead({ label }: { label: string }): JSX.Element | null {
    if (label === '') {
        return null;
    }
    return (
        <div className="builder-section-head">
            <span className="builder-section-head__label">{label}</span>
        </div>
    );
}

function CarouselSlides({
    slides,
    design,
    height,
    autoPlay,
    showDots,
}: {
    slides: Slide[];
    design: string;
    height: number;
    autoPlay: boolean;
    showDots: boolean;
}): JSX.Element {
    const [current, setCurrent] = useState(0);
    const count = slides.length;

    useEffect(() => {
        if (!autoPlay || count <= 1) {
            return;
        }
        const timer = window.setInterval(() => {
            setCurrent((prev) => (prev + 1) % count);
        }, 4500);
        return () => window.clearInterval(timer);
    }, [autoPlay, count]);

    return (
        <div
            className={`builder-carousel builder-carousel--design-${design}`}
            style={{ ['--carousel-h' as string]: `${height}px` }}
            data-auto-play={autoPlay ? '1' : '0'}
            data-slide-count={count}
        >
            <div className="builder-carousel__track">
                {slides.map((s, i) => (
                    <div
                        key={i}
                        className={`builder-carousel__slide${i === current ? ' is-active' : ''}`}
                        data-slide-index={i}
                    >
                        {s.image_url ? (
                            <img className="builder-carousel__img" src={s.image_url} alt="" loading="lazy" />
                        ) : null}
                        {s.caption ? (
                            <div className="builder-carousel__caption">
                                <span>{s.caption}</span>
                            </div>
                        ) : null}
                    </div>
                ))}
            </div>
            {showDots && count > 1 ? (
                <div className="builder-carousel__dots">
                    {slides.map((_, i) => (
                        <button
                            key={i}
                            type="button"
                            className={`builder-carousel__dot${i === current ? ' is-active' : ''}`}
                            data-slide-index={i}
                            aria-label={`スライド ${i + 1}`}
                            onClick={() => setCurrent(i)}
                        />
                    ))}
                </div>
            ) : null}
        </div>
    );
}

function initDomCarousel(slider: HTMLElement): () => void {
    const count = parseInt(slider.dataset.slideCount || '0', 10) || 0;
    if (count <= 1) {
        return () => undefined;
    }
    let current = 0;
    const showSlide = (idx: number): void => {
        slider.querySelectorAll('.builder-carousel__slide').forEach((s) => {
            const el = s as HTMLElement;
            el.classList.toggle('is-active', parseInt(el.dataset.slideIndex || '-1', 10) === idx);
        });
        slider.querySelectorAll('.builder-carousel__dot').forEach((d) => {
            const el = d as HTMLElement;
            el.classList.toggle('is-active', parseInt(el.dataset.slideIndex || '-1', 10) === idx);
        });
        current = idx;
    };
    const onDotClick = (e: Event): void => {
        const t = e.currentTarget as HTMLElement;
        showSlide(parseInt(t.dataset.slideIndex || '0', 10));
    };
    slider.querySelectorAll('.builder-carousel__dot').forEach((dot) => {
        dot.addEventListener('click', onDotClick);
    });
    let timer: number | undefined;
    if (slider.dataset.autoPlay === '1') {
        timer = window.setInterval(() => {
            showSlide((current + 1) % count);
        }, 4500);
    }
    return () => {
        if (timer !== undefined) {
            window.clearInterval(timer);
        }
        slider.querySelectorAll('.builder-carousel__dot').forEach((dot) => {
            dot.removeEventListener('click', onDotClick);
        });
    };
}

function GallerySourceSlider({ block }: SliderBlockProps): JSX.Element {
    const hostRef = useRef<HTMLElement>(null);
    const design = block.design != null ? String(block.design) : 'hero';
    const h = Number(block.slide_height != null ? block.slide_height : 200);
    const label = String(block.section_label || block.title || '');
    const autoPlay = block.auto_play !== false;
    const showDots = block.show_dots !== false;

    useEffect(() => {
        const el = hostRef.current;
        if (el === null) {
            return;
        }
        const head = label !== ''
            ? `<div class="builder-section-head"><span class="builder-section-head__label"></span></div>`
            : '';
        el.innerHTML = `${head}
            <div class="builder-carousel builder-carousel--loading" style="--carousel-h:${h}px">
                <div class="loading"><i class="fas fa-spinner fa-spin"></i><p>読み込み中...</p></div>
            </div>`;
        if (label !== '') {
            const labelEl = el.querySelector('.builder-section-head__label');
            if (labelEl !== null) {
                labelEl.textContent = label;
            }
        }

        const app = window.app as {
            galleryManager?: {
                loadCarousel?: (
                    container: Element,
                    options: {
                        design?: string;
                        slideHeight?: number;
                        autoPlay?: boolean;
                        showDots?: boolean;
                        onReady?: () => void;
                    },
                ) => Promise<void>;
            };
        } | undefined;
        if (app === undefined || typeof app.galleryManager?.loadCarousel !== 'function') {
            return;
        }

        let cancelled = false;
        let cleanup: (() => void) | undefined;
        void app.galleryManager.loadCarousel(el, {
            design,
            slideHeight: h,
            autoPlay,
            showDots,
            onReady: () => {
                if (cancelled) {
                    return;
                }
                const carousel = el.querySelector('.builder-carousel');
                if (carousel instanceof HTMLElement) {
                    cleanup = initDomCarousel(carousel);
                }
            },
        });
        return () => {
            cancelled = true;
            if (cleanup !== undefined) {
                cleanup();
            }
        };
    }, [design, h, autoPlay, showDots, label]);

    return (
        <section
            ref={hostRef}
            className={`builder-block builder-carousel-wrap builder-carousel-wrap--design-${design}`}
            style={baseStyleObject(block)}
            data-source="gallery_slider"
            data-design={design}
            data-slide-height={h}
            data-auto-play={autoPlay ? '1' : '0'}
            data-show-dots={showDots ? '1' : '0'}
        />
    );
}

export function SliderBlock({ block }: SliderBlockProps): JSX.Element {
    if (block.source === 'gallery') {
        return <GallerySourceSlider block={block} />;
    }

    const slides = (Array.isArray(block.slides) ? block.slides : []) as Slide[];
    const h = Number(block.slide_height != null ? block.slide_height : 200);
    const design = block.design != null ? String(block.design) : 'hero';
    const label = String(block.section_label || block.title || '');
    const base = baseStyleObject(block);

    if (slides.length === 0) {
        return (
            <section
                className={`builder-block builder-carousel builder-carousel--empty builder-carousel--design-${design}`}
                style={mergeStyles({ ['--carousel-h' as string]: `${h}px` }, base)}
            >
                <div className="builder-carousel__empty">
                    <i className="fas fa-images" aria-hidden="true" />
                </div>
            </section>
        );
    }

    return (
        <section className={`builder-block builder-carousel-wrap builder-carousel-wrap--design-${design}`} style={base}>
            <SectionHead label={label} />
            <CarouselSlides
                slides={slides}
                design={design}
                height={h}
                autoPlay={block.auto_play !== false}
                showDots={block.show_dots !== false}
            />
        </section>
    );
}
