import { ChartData, ChartOptions, Plugin } from 'chart.js';
import type { MessageResolver } from '../../../core/services/message.service';
import type { Observation, PeriodAverage, RegressionResult } from './evaluation-stats-http.service';

export function scoreDescriptor(value: number, t: MessageResolver): string | null {
  const v = Math.round(value);
  switch (v) {
    case 1: return t('es_lbl_score_bad');
    case 2: return t('es_lbl_score_regular');
    case 3: return t('es_lbl_score_good');
    case 4: return t('es_lbl_score_very_good');
    case 5: return t('es_lbl_score_excellent');
    default: return null;
  }
}

export const pointDescriptorPlugin = (t: MessageResolver): Plugin<'scatter'> => ({
  id: 'pointDescriptor',
  afterDatasetsDraw(chart) {
    const { ctx } = chart;
    const meta = chart.getDatasetMeta(0);
    if (!meta || !meta.data || meta.data.length === 0) return;

    ctx.save();
    ctx.fillStyle = '#0D1B5E';
    ctx.textAlign = 'center';

    const data = chart.data.datasets[0]?.data as ({ x: number; y: number } | null)[] | undefined;
    if (!data) return;

    for (let i = 0; i < meta.data.length; i++) {
      const el = meta.data[i] as { x: number; y: number };
      const raw = data[i];
      if (!raw) continue;
      const label = scoreDescriptor(raw.y, t);
      if (label === null) continue;

      ctx.font = '600 10px sans-serif';
      const metrics = ctx.measureText(label);
      const ascent = metrics.actualBoundingBoxAscent || 8;
      const topLimit = chart.chartArea.top ?? 0;
      const topGap = 6;
      ctx.textAlign = 'center';

      const labelWidth = metrics.width || 0;
      const halfW = labelWidth / 2;
      const minX = (chart.chartArea.left ?? 0) + halfW;
      const maxX = (chart.chartArea.right ?? ctx.canvas.width) - halfW;
      const safeX = Math.max(minX, Math.min(el.x, maxX));

      if (el.y - topGap - ascent < topLimit) {
        ctx.textBaseline = 'top';
        ctx.fillText(label, safeX, el.y + 5);
      } else {
        ctx.textBaseline = 'bottom';
        ctx.fillText(label, safeX, el.y - topGap);
      }
    }

    ctx.restore();
  },
});

interface XY { x: number; y: number; }

function indexedRegression(points: XY[]): { slope: number; intercept: number } {
  const n = points.length;
  const sumX = points.reduce((s, p) => s + p.x, 0);
  const sumY = points.reduce((s, p) => s + p.y, 0);
  const sumXY = points.reduce((s, p) => s + p.x * p.y, 0);
  const sumXX = points.reduce((s, p) => s + p.x * p.x, 0);
  const denom = n * sumXX - sumX * sumX;
  if (denom === 0) {
    return { slope: 0, intercept: sumY / n };
  }
  const slope = (n * sumXY - sumX * sumY) / denom;
  const intercept = (sumY - slope * sumX) / n;
  return { slope, intercept };
}

export function analysisChartData(
  observations: Observation[],
  regression: RegressionResult | null,
  t: MessageResolver,
): ChartData<'scatter'> {
  const points: XY[] = observations.map((o, i) => ({ x: o.index ?? i + 1, y: o.value }));
  const datasets: ChartData<'scatter'>['datasets'] = [{
    label: t('es_lbl_observations'),
    data: points,
    backgroundColor: '#0D1B5E',
    borderColor: '#0D1B5E',
    pointRadius: 5,
    showLine: false,
  }];

  if (regression && observations.length >= 2) {
    const xs = points.map(p => p.x);
    const xMin = Math.min(...xs);
    const xMax = Math.max(...xs);
    const line = indexedRegression(points);
    datasets.push({
      label: t('es_lbl_regression'),
      data: [
        { x: xMin, y: line.slope * xMin + line.intercept },
        { x: xMax, y: line.slope * xMax + line.intercept },
      ],
      borderColor: '#F3E12D',
      backgroundColor: '#F3E12D',
      borderDash: [5, 5],
      pointRadius: 0,
      showLine: true,
      tension: 0,
      borderWidth: 2,
    });
  }

  return { datasets };
}

export function analysisChartOptions(
  observations: Observation[],
  t: MessageResolver,
  animation = true,
): ChartOptions<'scatter'> {
  const options: ChartOptions<'scatter'> = {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: {
        callbacks: {
          label: ctx => {
            const obs = observations[ctx.dataIndex];
            const point = ctx.raw as { x: number; y: number };
            if (obs) {
              return t('es_lbl_obs_tooltip', { date: obs.evaluated_at, value: point.y, residual: obs.residual });
            }
            return `${point.y}`;
          },
        },
      },
    },
    scales: {
      x: {
        type: 'linear',
        title: { display: true, text: t('es_lbl_days') },
        afterBuildTicks: (axis) => {
          axis.ticks = observations.map(o => ({ value: o.index }));
        },
        ticks: {
          callback(value: string | number) {
            const idx = Number(value);
            const o = observations.find(p => p.index === idx);
            return o ? t('es_lbl_obs_xtick', { days: o.x_days }) : '';
          },
        },
      },
      y: { beginAtZero: true },
    },
  };

  if (animation === false) {
    options.animation = false;
  }

  return options;
}

export function historialChartData(
  averages: PeriodAverage[],
  regression: RegressionResult | null,
  t: MessageResolver,
): ChartData<'scatter'> {
  const datasets: ChartData<'scatter'>['datasets'] = [{
    label: t('es_lbl_avg_per_period'),
    data: averages.map(a => ({ x: a.index, y: a.average })),
    backgroundColor: '#0D1B5E',
    borderColor: '#0D1B5E',
    pointRadius: 5,
    showLine: true,
    tension: 0.3,
  }];

  if (regression && averages.length >= 2) {
    const xs = averages.map(a => a.index);
    const xMin = Math.min(...xs);
    const xMax = Math.max(...xs);
    datasets.push({
      label: t('es_lbl_regression'),
      data: [
        { x: xMin, y: regression.slope * xMin + regression.intercept },
        { x: xMax, y: regression.slope * xMax + regression.intercept },
      ],
      borderColor: '#F3E12D',
      backgroundColor: '#F3E12D',
      borderDash: [5, 5],
      pointRadius: 0,
      showLine: true,
      tension: 0,
      borderWidth: 2,
    });
  }

  return { datasets };
}

export function historialChartOptions(
  averages: PeriodAverage[],
  t: MessageResolver,
  animation = true,
): ChartOptions<'scatter'> {
  const options: ChartOptions<'scatter'> = {
    responsive: true,
    plugins: {
      legend: { position: 'bottom' },
      tooltip: {
        callbacks: {
          label: ctx => {
            const raw = ctx.raw as { x: number; y: number };
            const a = averages.find(p => p.index === raw.x);
            const name = a ? a.period_name : `${raw.x}`;
            return t('es_lbl_avg_tooltip', { period: name, average: raw.y });
          },
        },
      },
    },
    scales: {
      x: {
        type: 'linear',
        title: { display: true, text: t('es_lbl_period') },
        ticks: {
          callback: (value: string | number) => {
            const a = averages.find(p => p.index === Number(value));
            return a ? a.period_name : value;
          },
        },
      },
      y: { beginAtZero: true },
    },
  };

  if (animation === false) {
    options.animation = false;
  }

  return options;
}