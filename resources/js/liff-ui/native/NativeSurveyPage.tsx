import { useState } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { NativeRequestError, NativeState, nativeRequest, useNativeResource } from "./NativeResource";
import { isDemoDataMode } from "../../frontend/demo/demoMode";
import { OTHER_KEY, OTHER_MAX, answerPayload, isAnswered, npsTone, type AnswerValue } from "./surveyAnswers";

type Question = { type: "nps" | "choice" | "multi_choice" | "rating" | "number" | "date" | "text"; label: string; help?: string; options?: string[]; allow_other?: boolean; required?: boolean };
type Survey = { id: number; title: string; description?: string | null; questions: Question[]; reward_points: number; is_open: boolean; answered: boolean };

const OTHER_LABEL = "その他（自由入力）";

const sampleSurvey: Survey = {
  id: 1, title: "ご来店アンケート", description: "サービス向上のため、ご意見をお聞かせください。", reward_points: 50, is_open: true, answered: false,
  questions: [
    { type: "nps", label: "このお店を友人や知人にすすめる可能性はどのくらいありますか？", required: true },
    { type: "rating", label: "接客の満足度", required: true },
    { type: "choice", label: "ご利用のきっかけ", options: ["ランチ", "ディナー"], allow_other: true },
    { type: "text", label: "ご意見・ご感想", help: "改善してほしい点もお聞かせください" },
  ],
};

/** お知らせ・LINE・QRから開くアンケート回答画面。?survey= が無ければ回答できる一覧を出す。 */
export function NativeSurveyPage() {
  const [search] = useSearchParams();
  const id = search.get("survey") ?? "";
  return /^\d+$/.test(id) ? <SurveyForm key={id} id={id} /> : <SurveyList />;
}

function SurveyList() {
  const resource = useNativeResource<{ surveys?: Survey[] }>("/api/surveys", { surveys: [sampleSurvey] });
  const surveys = resource.data.surveys ?? [];
  return <div className="native-screen native-surveys"><NativeState loading={resource.loading} error={resource.error} empty={!surveys.length} title="回答できるアンケートはありません" subtitle="アンケートが届くとここに表示されます" icon="fa-square-poll-vertical" reload={() => void resource.reload()}>
    {surveys.map((survey) => <Link className="native-survey-row" key={survey.id} to={`/survey?survey=${survey.id}`}>
      <span className="native-survey-row-icon" aria-hidden="true"><i className="fa-solid fa-square-poll-vertical" /></span>
      <span className="native-survey-row-text"><strong>{survey.title}</strong><small>{survey.questions.length}問{survey.answered ? " · 回答済み" : ""}</small></span>
      {!survey.answered && survey.reward_points > 0 ? <span className="native-survey-reward">{survey.reward_points}pt</span> : null}
      <i className="fa-solid fa-chevron-right native-survey-row-chevron" aria-hidden="true" />
    </Link>)}
  </NativeState></div>;
}

function SurveyForm({ id }: { id: string }) {
  const resource = useNativeResource<{ survey?: Survey }>(`/api/surveys/${id}`, { survey: { ...sampleSurvey, id: Number(id) } });
  const survey = resource.data.survey;
  const [answers, setAnswers] = useState<Record<number, AnswerValue>>({});
  const [others, setOthers] = useState<Record<number, string>>({});
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [message, setMessage] = useState("");
  const [busy, setBusy] = useState(false);
  const [grantedPoints, setGrantedPoints] = useState<number | null>(null);
  const answer = (index: number, value: AnswerValue) => setAnswers((current) => ({ ...current, [index]: value }));
  const other = (index: number, text: string) => setOthers((current) => ({ ...current, [index]: text }));

  async function submit() {
    if (!survey || busy) return;
    setBusy(true); setErrors({}); setMessage("");
    try {
      const payload = survey.questions.map((_, index) => answerPayload(answers[index], others[index]));
      const result = isDemoDataMode() ? { granted_points: survey.reward_points } : await nativeRequest<{ granted_points: number }>(`/api/surveys/${survey.id}/answers`, { answers: payload });
      setGrantedPoints(result.granted_points);
      document.dispatchEvent(new CustomEvent("liff-member-reload"));
    } catch (caught) {
      if (caught instanceof NativeRequestError) setErrors(caught.fieldErrors);
      setMessage(caught instanceof Error ? caught.message : "送信できませんでした");
    } finally { setBusy(false); }
  }

  const finished = (icon: string, title: string, text: string) => <div className="native-state native-survey-done" role="status"><i className={`fa-solid ${icon}`} aria-hidden="true" /><h2>{title}</h2><p>{text}</p><Link className="native-button" to="/home">ホームへ戻る</Link></div>;
  if (grantedPoints !== null) return <div className="native-screen">{finished("fa-circle-check", "ご回答ありがとうございました", grantedPoints > 0 ? `${grantedPoints}ポイントを付与しました。` : "いただいたご意見はサービス向上に活用します。")}</div>;

  const total = survey?.questions.length ?? 0;
  const done = survey?.questions.filter((_, index) => isAnswered(answers[index], others[index])).length ?? 0;

  return <div className="native-screen native-survey"><NativeState loading={resource.loading} error={resource.error} empty={!survey} title="アンケートが見つかりません" icon="fa-square-poll-vertical" reload={() => void resource.reload()}>
    {survey?.answered ? finished("fa-circle-check", "回答済みです", "このアンケートにはすでに回答いただいています。") : !survey?.is_open ? finished("fa-clock", "受付を終了しました", "このアンケートの回答受付は終了しています。") : survey ? <form className="native-form native-survey-form" noValidate onSubmit={(event) => { event.preventDefault(); void submit(); }}>
      <header className="native-survey-header">
        <h1>{survey.title}</h1>
        {survey.description ? <p>{survey.description}</p> : null}
        <div className="native-survey-meta">
          <span><i className="fa-regular fa-clock" aria-hidden="true" /> 全{total}問</span>
          {survey.reward_points > 0 ? <span className="native-survey-reward"><i className="fa-solid fa-gift" aria-hidden="true" /> 回答で{survey.reward_points}ポイント</span> : null}
        </div>
      </header>
      <div className="native-survey-progress" role="progressbar" aria-label="回答の進み具合" aria-valuemin={0} aria-valuemax={total} aria-valuenow={done}>
        <span style={{ width: `${total ? (done / total) * 100 : 0}%` }} />
      </div>
      {survey.questions.map((question, index) => {
        const error = errors[`answers.${index}`];
        const errorId = `survey-error-${index}`;
        const helpId = `survey-help-${index}`;
        const described = [question.help ? helpId : "", error ? errorId : ""].filter(Boolean).join(" ") || undefined;
        return <fieldset className={`native-survey-question${error ? " has-error" : ""}`} key={index} aria-describedby={described}>
          <legend><span className="native-survey-number">Q{index + 1}</span>{question.label}{question.required ? <span className="native-survey-required">必須</span> : null}</legend>
          {question.help ? <p className="native-survey-help" id={helpId}>{question.help}</p> : null}
          <SurveyQuestionField question={question} index={index} value={answers[index]} otherText={others[index] ?? ""} onChange={(value) => answer(index, value)} onOther={(text) => other(index, text)} />
          {error ? <p className="native-text-danger" id={errorId}>{error}</p> : null}
        </fieldset>;
      })}
      <div className="native-survey-submit">
        {message ? <p className="native-text-danger" role="alert">{message}</p> : null}
        <button className="native-button" disabled={busy}>{busy ? "送信中…" : "回答を送信する"}</button>
      </div>
    </form> : null}
  </NativeState></div>;
}

type FieldProps = { question: Question; index: number; value: AnswerValue | undefined; otherText: string; onChange: (value: AnswerValue) => void; onOther: (text: string) => void };

/** 質問の形式ごとの入力欄。 */
function SurveyQuestionField({ question, index, value, otherText, onChange, onOther }: FieldProps) {
  const name = `q${index}`;
  switch (question.type) {
    case "nps": return <>
      <div className="native-survey-nps">{Array.from({ length: 11 }, (_, score) => <label key={score} className={value === score ? `is-selected is-${npsTone(score)}` : ""}><input type="radio" name={name} value={score} checked={value === score} onChange={() => onChange(score)} />{score}</label>)}</div>
      <div className="native-survey-scale" aria-hidden="true"><span>すすめない</span><span>ぜひすすめる</span></div>
    </>;
    case "rating": return <div className="native-survey-stars">{[1, 2, 3, 4, 5].map((score) => <label key={score} className={typeof value === "number" && score <= value ? "is-on" : ""}><input type="radio" name={name} value={score} checked={value === score} onChange={() => onChange(score)} aria-label={`${score}つ星`} /><i className="fa-solid fa-star" aria-hidden="true" /></label>)}<span className="native-survey-stars-label" aria-hidden="true">{typeof value === "number" ? ["", "不満", "やや不満", "ふつう", "満足", "とても満足"][value] : "タップして評価"}</span></div>;
    case "choice":
    case "multi_choice": {
      const multi = question.type === "multi_choice";
      const selected = Array.isArray(value) ? value : [];
      const isChecked = (key: string) => (multi ? selected.includes(key) : value === key);
      const toggle = (key: string, checked: boolean) => onChange(multi ? (checked ? [...selected, key] : selected.filter((item) => item !== key)) : key);
      const options = [...(question.options ?? []).map((option) => ({ key: option, label: option })), ...(question.allow_other ? [{ key: OTHER_KEY, label: OTHER_LABEL }] : [])];
      return <div className="native-survey-options">
        {multi && !question.help ? <p className="native-survey-help">当てはまるものをすべて選んでください</p> : null}
        {options.map((option) => <label key={option.key} className={`native-survey-choice${isChecked(option.key) ? " is-selected" : ""}`}><input type={multi ? "checkbox" : "radio"} name={name} value={option.key} checked={isChecked(option.key)} onChange={(event) => toggle(option.key, event.target.checked)} /><span>{option.label}</span></label>)}
        {isChecked(OTHER_KEY) ? <input type="text" className="native-survey-other" aria-label={`${question.label}（その他の内容）`} placeholder="内容を入力してください" maxLength={OTHER_MAX} value={otherText} onChange={(event) => onOther(event.target.value)} autoFocus /> : null}
      </div>;
    }
    case "number": return <input type="number" inputMode="decimal" aria-label={question.label} placeholder="数字を入力" value={typeof value === "number" || typeof value === "string" ? value : ""} onChange={(event) => onChange(event.target.value)} />;
    case "date": return <input type="date" aria-label={question.label} value={typeof value === "string" ? value : ""} onChange={(event) => onChange(event.target.value)} />;
    default: return <textarea aria-label={question.label} maxLength={1000} rows={4} placeholder="自由にご記入ください" value={typeof value === "string" ? value : ""} onChange={(event) => onChange(event.target.value)} />;
  }
}
