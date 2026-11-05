import { Injectable, inject } from '@angular/core';
import { jsPDF } from 'jspdf';
import { Chart } from 'chart.js';
import type { ChartData, ChartOptions } from 'chart.js';
import { MessageService, MessageResolver } from '../../../core/services/message.service';
import { ConfigService } from '../../../core/services/config.service';
import { PlayerStatsResponse, QuestionStats, QuestionnairePlayerStats, RegressionResult } from './evaluation-stats-http.service';
import {
  analysisChartData,
  analysisChartOptions,
  historialChartData,
  historialChartOptions,
  pointDescriptorPlugin,
} from './chart-config';

const PAGE_W = 210;
const PAGE_H = 297;
const MARGIN = 15;
const GRID_COLS = 3;
const CARD_GAP = 4;
const CARD_W = (PAGE_W - 2 * MARGIN - (GRID_COLS - 1) * CARD_GAP) / GRID_COLS;
const CARD_PAD = 3;
const CARD_TITLE_OFFSET = 5;

const CHART_SM_W = 500;
const CHART_SM_H = 250;
const CHART_SM_PDF_W = CARD_W - 2 * CARD_PAD - 2;
const CHART_SM_PDF_H = (CHART_SM_PDF_W * CHART_SM_H) / CHART_SM_W;
const TREND_FOOTER_H = 8;
const CARD_PREG_H = 12 + 2 * CHART_SM_PDF_H + 2 * CARD_PAD + 4 + TREND_FOOTER_H;
const CARD_FICHA_H = 52;

const COLOR_PRIMARY: [number, number, number] = [13, 27, 94];
const COLOR_SECONDARY: [number, number, number] = [75, 85, 99];
const COLOR_CARD_BG: [number, number, number] = [247, 248, 252];
const COLOR_CARD_BORDER: [number, number, number] = [217, 222, 231];
const LOGO_H = 16;
const LOGO_MAX_W = 50;
const FOOTER_Y = PAGE_H - 10;

@Injectable({ providedIn: 'root' })
export class ReportService {
  private msg = inject(MessageService);
  private config = inject(ConfigService);
  private readonly t: MessageResolver = (key, params) => this.msg.get(key, params);

  private y = 0;
  private currentPlayerName = '';
  private currentBirthDate = '';
  private logoDataUrl: string | null = null;
  private logoAspect = 1;

  async generatePdf(stats: PlayerStatsResponse, playerName: string | null): Promise<void> {
    if (this.logoDataUrl === null && this.config.logoUrl) {
      await this.loadLogo();
    }

    const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
    const container = this.createContainer();
    this.currentPlayerName = (playerName ?? stats.target_user.display_name) || '';
    this.currentBirthDate = stats.target_user.birth_date ?? '';

    try {
      this.drawHeader(doc);

      for (const q of stats.questionnaires) {
        this.drawQuestionnaire(doc, container, stats, q);
      }
    } finally {
      container.remove();
    }

    const total = doc.getNumberOfPages();
    for (let i = 1; i <= total; i++) {
      doc.setPage(i);
      this.drawFooter(doc, i, total);
    }

    const safeName =
      (playerName ?? 'jugador').trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') ||
      'jugador';
    doc.save(`ficha-tecnica-${safeName}.pdf`);
  }

  private createContainer(): HTMLDivElement {
    const el = document.createElement('div');
    el.style.position = 'fixed';
    el.style.left = '-99999px';
    el.style.top = '0';
    el.style.width = `${CHART_SM_W}px`;
    el.style.zIndex = '-1';
    document.body.appendChild(el);
    return el;
  }

  private async loadLogo(): Promise<void> {
    try {
      const res = await fetch(this.config.logoUrl);
      if (!res.ok) return;
      const blob = await res.blob();
      const dataUrl = await new Promise<string>((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result));
        reader.onerror = () => reject(reader.error);
        reader.readAsDataURL(blob);
      });
      const img = await new Promise<HTMLImageElement>((resolve, reject) => {
        const el = new Image();
        el.onload = () => resolve(el);
        el.onerror = () => reject(el);
        el.src = dataUrl;
      });
      this.logoDataUrl = dataUrl;
      this.logoAspect = img.naturalHeight > 0 ? img.naturalWidth / img.naturalHeight : 1;
    } catch {
      this.logoDataUrl = null;
    }
  }

  private drawFooter(doc: jsPDF, page: number, total: number): void {
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.setTextColor(COLOR_SECONDARY[0], COLOR_SECONDARY[1], COLOR_SECONDARY[2]);
    doc.text(`${page} / ${total}`, PAGE_W / 2, FOOTER_Y, { align: 'center' });
  }

  private drawHeader(doc: jsPDF): void {
    if (this.logoDataUrl) {
      const w = Math.min(LOGO_H * this.logoAspect, LOGO_MAX_W);
      doc.addImage(this.logoDataUrl, 'JPEG', PAGE_W - MARGIN - w, 12, w, LOGO_H);
    }

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(16);
    doc.setTextColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
    doc.text(`${this.t('es_lbl_report_title')} — ${this.t('es_lbl_report_school')}`, MARGIN, 20);

    doc.setFont('helvetica', 'normal');
    doc.setFontSize(10);
    doc.setTextColor(COLOR_SECONDARY[0], COLOR_SECONDARY[1], COLOR_SECONDARY[2]);
    const metaParts = [`${this.t('es_lbl_player')}: ${this.currentPlayerName}`];
    if (this.currentBirthDate) {
      metaParts.push(`${this.t('es_lbl_birth_date')}: ${this.currentBirthDate}`);
    }
    metaParts.push(`${this.t('es_lbl_report_date')}: ${new Date().toLocaleDateString()}`);
    doc.text(`${metaParts.join('   |   ')}`, MARGIN, 26);

    doc.setDrawColor(COLOR_CARD_BORDER[0], COLOR_CARD_BORDER[1], COLOR_CARD_BORDER[2]);
    doc.line(MARGIN, 29, PAGE_W - MARGIN, 29);
    this.y = 34;
  }

  private ensureSpace(doc: jsPDF, needed: number): void {
    if (this.y + needed > PAGE_H - MARGIN) {
      doc.addPage('a4', 'portrait');
      this.drawHeader(doc);
    }
  }

  private textBlock(
    doc: jsPDF,
    text: string,
    size: number,
    opts: { bold?: boolean; color?: [number, number, number] } = {},
  ): void {
    const lines = doc.splitTextToSize(text, PAGE_W - 2 * MARGIN);
    const lineH = size * 0.38;
    this.ensureSpace(doc, lines.length * lineH + 3);

    doc.setFont('helvetica', opts.bold ? 'bold' : 'normal');
    doc.setFontSize(size);
    const color = opts.color ?? COLOR_PRIMARY;
    doc.setTextColor(color[0], color[1], color[2]);
    doc.text(lines, MARGIN, this.y);
    this.y += lines.length * lineH + 3;
  }

  private drawQuestionnaire(
    doc: jsPDF,
    container: HTMLElement,
    stats: PlayerStatsResponse,
    q: QuestionnairePlayerStats,
  ): void {
    this.ensureSpace(doc, 14);
    this.textBlock(doc, `${this.t('es_lbl_evaluation')}: ${q.questionnaire.title}`, 13, { bold: true });
    this.y += 1;

    if (q.last_observation) {
      this.ensureSpace(doc, 26);
      this.drawCard(doc, MARGIN, this.y, PAGE_W - 2 * MARGIN, 24);
      this.cardTitle(doc, this.t('es_lbl_last_observation'), MARGIN + CARD_PAD, this.y + CARD_TITLE_OFFSET);
      const evaluator = q.last_evaluator ?? '';
      const date = q.last_observation_at ?? '';
      const meta = [evaluator, date].filter(Boolean).join(' | ');
      if (meta) {
        this.cardTextSecondary(doc, meta, MARGIN + CARD_PAD, this.y + CARD_PAD + 5);
        this.cardText(doc, q.last_observation, MARGIN + CARD_PAD, this.y + CARD_PAD + 10, 10);
      } else {
        this.cardText(doc, q.last_observation, MARGIN + CARD_PAD, this.y + CARD_PAD + 5, 10);
      }
      this.y += 28;
    }

    this.ensureSpace(doc, CARD_FICHA_H + CARD_GAP);
    this.drawPlayerCard(doc, MARGIN, this.y, PAGE_W * 0.5, CARD_FICHA_H, stats.target_user);
    this.y += CARD_FICHA_H + CARD_GAP;

    const totalPreg = q.per_question.length;
    for (let i = 0; i < totalPreg; i += GRID_COLS) {
      const n = Math.min(GRID_COLS, totalPreg - i);
      this.ensureSpace(doc, CARD_PREG_H + CARD_GAP);
      for (let c = 0; c < n; c++) {
        const pq = q.per_question[i + c];
        const x = MARGIN + c * (CARD_W + CARD_GAP);
        this.drawQuestionCard(doc, container, x, this.y, CARD_W, CARD_PREG_H, pq);
      }
      this.y += CARD_PREG_H + CARD_GAP;
    }

    if (q.last_observation) {
      this.ensureSpace(doc, 26);
      this.drawCard(doc, MARGIN, this.y, PAGE_W - 2 * MARGIN, 24);
      this.cardTitle(doc, this.t('es_card_comments'), MARGIN + CARD_PAD, this.y + CARD_TITLE_OFFSET);
      const evaluator = q.last_evaluator ?? '';
      const date = q.last_observation_at ?? '';
      const meta = [evaluator, date].filter(Boolean).join(' | ');
      if (meta) {
        this.cardTextSecondary(doc, meta, MARGIN + CARD_PAD, this.y + CARD_PAD + 5);
        this.cardText(doc, q.last_observation, MARGIN + CARD_PAD, this.y + CARD_PAD + 10, 10);
      } else {
        this.cardText(doc, q.last_observation, MARGIN + CARD_PAD, this.y + CARD_PAD + 5, 10);
      }
      this.y += 28;
    } else {
      this.ensureSpace(doc, 26);
      this.drawCard(doc, MARGIN, this.y, PAGE_W - 2 * MARGIN, 24);
      this.cardTitle(doc, this.t('es_card_comments'), MARGIN + CARD_PAD, this.y + CARD_TITLE_OFFSET);
      this.cardText(doc, this.t('es_card_no_comments'), MARGIN + CARD_PAD, this.y + CARD_PAD + 5, 10);
      this.y += 28;
    }
  }

  private drawCard(doc: jsPDF, x: number, y: number, w: number, h: number): void {
    doc.setFillColor(COLOR_CARD_BG[0], COLOR_CARD_BG[1], COLOR_CARD_BG[2]);
    doc.setDrawColor(COLOR_CARD_BORDER[0], COLOR_CARD_BORDER[1], COLOR_CARD_BORDER[2]);
    doc.setLineWidth(0.3);
    doc.roundedRect(x, y, w, h, 2, 2, 'FD');
  }

  private cardTitle(doc: jsPDF, text: string, x: number, y: number): void {
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
    doc.text(text, x, y);
  }

  private cardLabel(doc: jsPDF, text: string, x: number, y: number): void {
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(COLOR_SECONDARY[0], COLOR_SECONDARY[1], COLOR_SECONDARY[2]);
    doc.text(text, x, y);
  }

  private cardText(doc: jsPDF, text: string, x: number, y: number, size: number, maxW?: number): void {
    const w = maxW ?? (CARD_W - 2 * CARD_PAD - 2);
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(size);
    doc.setTextColor(30, 40, 60);
    const lines = doc.splitTextToSize(text, w);
    doc.text(lines, x, y);
  }

  private cardTextSecondary(doc: jsPDF, text: string, x: number, y: number): void {
    doc.setFont('helvetica', 'italic');
    doc.setFontSize(8);
    doc.setTextColor(COLOR_SECONDARY[0], COLOR_SECONDARY[1], COLOR_SECONDARY[2]);
    doc.text(text, x, y);
  }

  private drawPlayerCard(doc: jsPDF, x: number, y: number, w: number, h: number, target: PlayerStatsResponse['target_user']): void {
    this.drawCard(doc, x, y, w, h);
    this.cardTitle(doc, this.t('es_card_player_info'), x + CARD_PAD, y + CARD_TITLE_OFFSET);

    const innerW = w - 2 * CARD_PAD;
    const colW = innerW / 2;
    const leftX = x + CARD_PAD;
    const rightX = x + CARD_PAD + colW;

    const name = target.display_name || this.currentPlayerName;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(10);
    doc.setTextColor(30, 40, 60);
    const nameLines = doc.splitTextToSize(name, colW - 2);
    doc.text(nameLines, leftX, y + CARD_PAD + 8);

    let leftColY = y + CARD_PAD + 8 + nameLines.length * 4;
    if (target.email) {
      this.cardLabel(doc, `${this.t('es_lbl_email')}:`, leftX, leftColY);
      const emailLines = doc.splitTextToSize(target.email, colW - 2);
      doc.setFont('helvetica', 'normal');
      doc.setFontSize(8.5);
      doc.setTextColor(30, 40, 60);
      doc.text(emailLines, leftX, leftColY + 4);
      leftColY += 4 + emailLines.length * 3.5;
    }

    const rows: [string, string | undefined | null][] = [
      [this.t('es_lbl_birth_date'), target.birth_date],
      [this.t('es_lbl_height'), target.height_cm != null ? `${target.height_cm} cm` : undefined],
      [this.t('es_lbl_weight'), target.weight_kg != null ? `${target.weight_kg} kg` : undefined],
      [this.t('es_lbl_team'), target.team],
    ];
    let rightY = y + CARD_PAD + 7;
    for (const [label, value] of rows) {
      if (value == null || value === '') continue;
      this.cardLabel(doc, `${label}:`, rightX, rightY);
      this.cardText(doc, String(value), rightX + 28, rightY, 8.5, colW - 28 - 2);
      rightY += 5;
    }
  }

  private drawQuestionCard(
    doc: jsPDF,
    container: HTMLElement,
    x: number,
    y: number,
    w: number,
    h: number,
    pq: QuestionStats,
  ): void {
    this.drawCard(doc, x, y, w, h);

    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8);
    doc.setTextColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
    const titleLines = doc.splitTextToSize(`P ${pq.question_id}: ${pq.question_text}`, w - 2 * CARD_PAD);
    doc.text(titleLines, x + CARD_PAD, y + CARD_PAD + 1.5);

    const chartTop = y + CARD_PAD + titleLines.length * 3 + 1;

    const dataAnalysis = analysisChartData(pq.observations, pq.regression, this.t);
    const optsAnalysis = analysisChartOptions(pq.observations, this.t, false);
    this.addChartSmall(doc, container, x + CARD_PAD + 1, chartTop, CHART_SM_PDF_W, CHART_SM_PDF_H, dataAnalysis, optsAnalysis);

    const dataHist = historialChartData(pq.averages, pq.historial_regression, this.t);
    const optsHist = historialChartOptions(pq.averages, this.t, false);
    this.addChartSmall(doc, container, x + CARD_PAD + 1, chartTop + CHART_SM_PDF_H + 2, CHART_SM_PDF_W, CHART_SM_PDF_H, dataHist, optsHist);

    if (pq.regression !== null) {
      this.drawTrendFooter(doc, x, y, w, h, pq.regression);
    }
  }

  private drawTrendFooter(doc: jsPDF, x: number, y: number, w: number, h: number, regression: RegressionResult): void {
    const footerY = y + h - 6;
    doc.setDrawColor(COLOR_CARD_BORDER[0], COLOR_CARD_BORDER[1], COLOR_CARD_BORDER[2]);
    doc.setLineWidth(0.3);
    doc.line(x + CARD_PAD, footerY - 3, x + w - CARD_PAD, footerY - 3);

    const label = `${this.t('es_lbl_trend')}:`;
    doc.setFont('helvetica', 'normal');
    doc.setFontSize(8.5);
    doc.setTextColor(30, 40, 60);
    doc.text(label, x + CARD_PAD, footerY);

    const valueX = x + CARD_PAD + doc.getTextWidth(label) + 1;
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(8.5);
    doc.setTextColor(COLOR_PRIMARY[0], COLOR_PRIMARY[1], COLOR_PRIMARY[2]);
    doc.text(String(regression.slope), valueX, footerY);

    let trendKey: string;
    let color: [number, number, number];
    if (regression.slope < 0) {
      trendKey = 'es_lbl_trend_decreased';
      color = [220, 53, 69];
    } else if (regression.slope > 0) {
      trendKey = 'es_lbl_trend_improved';
      color = [40, 167, 69];
    } else {
      trendKey = 'es_lbl_trend_stable';
      color = [23, 162, 184];
    }

    const trendText = this.t(trendKey);
    const badgeH = 4;
    const badgePad = 2;
    const badgeW = doc.getTextWidth(trendText) + 2 * badgePad;
    const badgeX = x + w - CARD_PAD - badgeW;
    const badgeY = footerY - badgeH + 0.5;
    doc.setFillColor(color[0], color[1], color[2]);
    doc.roundedRect(badgeX, badgeY, badgeW, badgeH, 1, 1, 'F');
    doc.setFont('helvetica', 'bold');
    doc.setFontSize(7);
    doc.setTextColor(255, 255, 255);
    doc.text(trendText, badgeX + badgePad, footerY - 0.3);
  }

  private addChartSmall(
    doc: jsPDF,
    container: HTMLElement,
    x: number,
    y: number,
    w: number,
    h: number,
    data: ChartData<'scatter'>,
    options: ChartOptions<'scatter'>,
  ): void {
    const dataUrl = this.renderChart(container, data, options);
    doc.addImage(dataUrl, 'PNG', x, y, w, h);
  }

  private renderChart(
    container: HTMLElement,
    data: ChartData<'scatter'>,
    options: ChartOptions<'scatter'>,
  ): string {
    const canvas = document.createElement('canvas');
    canvas.width = CHART_SM_W;
    canvas.height = CHART_SM_H;
    canvas.style.width = `${CHART_SM_W}px`;
    canvas.style.height = `${CHART_SM_H}px`;
    container.appendChild(canvas);

    const chart = new Chart(canvas, {
      type: 'scatter',
      data,
      options: { ...options, responsive: false, animation: false },
      plugins: [pointDescriptorPlugin(this.t)],
    });

    try {
      return chart.toBase64Image('image/png', 1);
    } finally {
      chart.destroy();
      canvas.remove();
    }
  }
}
