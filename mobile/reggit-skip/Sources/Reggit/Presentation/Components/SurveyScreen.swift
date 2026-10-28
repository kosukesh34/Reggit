import SwiftUI

/// お知らせ・ポップアップから開くアンケート画面。番号がなければ回答できる一覧を出す。
struct SurveyScreen: View {
    let onClose: () -> Void
    @State var viewModel: SurveyViewModel
    @State var selectedID: Int?
    @State var dates: [Int: Date] = [:]
    @Environment(\.brandAccent) var accent

    private static let ratingWords = ["不満", "やや不満", "ふつう", "満足", "とても満足"]
    private static let passive = Color(red: 0.88, green: 0.63, blue: 0.0)

    init(service: any SurveyServing, surveyID: Int?, onClose: @escaping () -> Void) {
        self.onClose = onClose
        _viewModel = State(initialValue: SurveyViewModel(service: service))
        _selectedID = State(initialValue: surveyID)
    }

    /// 回答フォームを出している間のアンケート（送信ボタンと進み具合を出す）
    private var answering: SurveyDetail? {
        guard !viewModel.isLoading, viewModel.grantedPoints == nil, let survey = viewModel.survey, !survey.answered, survey.isOpen else { return nil }
        return survey
    }

    var body: some View {
        VStack(spacing: 0) {
            HStack {
                Text("アンケート").font(.headline).foregroundStyle(AppDesign.ink)
                Spacer()
                Button(action: onClose) {
                    FontAwesomeIcon(systemName: "xmark")
                        .frame(width: 44, height: 44)
                        .foregroundStyle(AppDesign.inkSecondary)
                }
                .buttonStyle(.plain)
                .accessibilityLabel(Text("閉じる"))
            }
            .padding(.horizontal, 16)
            if let survey = answering {
                progress(answered: viewModel.answeredCount, total: survey.questions.count)
            }
            ScrollView {
                content.padding(16)
            }
            if answering != nil {
                submitBar
            }
        }
        .background(AppDesign.canvas.ignoresSafeArea())
        .task(id: selectedID) { await viewModel.load(surveyID: selectedID) }
    }

    @ViewBuilder
    private var content: some View {
        if viewModel.isLoading {
            ProgressView().frame(maxWidth: .infinity, minHeight: 200)
        } else if let points = viewModel.grantedPoints {
            finished(icon: "checkmark.circle.fill", title: "ご回答ありがとうございました", text: points > 0 ? "\(points)ポイントを付与しました。" : "いただいたご意見はサービス向上に活用します。")
        } else if let survey = viewModel.survey {
            if survey.answered {
                finished(icon: "checkmark.circle.fill", title: "回答済みです", text: "このアンケートにはすでに回答いただいています。")
            } else if !survey.isOpen {
                finished(icon: "clock.arrow.circlepath", title: "受付を終了しました", text: "このアンケートの回答受付は終了しています。")
            } else {
                form(survey)
            }
        } else if let message = viewModel.errorMessage {
            AppEmptyState(title: "読み込めませんでした", subtitle: message, systemImage: "wifi.exclamationmark", actionTitle: "再読み込み") {
                Task { await viewModel.load(surveyID: selectedID) }
            }
        } else if viewModel.surveys.isEmpty {
            AppEmptyState(title: "回答できるアンケートはありません", subtitle: "アンケートが届くとここに表示されます", systemImage: "list.clipboard")
        } else {
            VStack(spacing: 10) {
                ForEach(viewModel.surveys) { survey in
                    Button { selectedID = survey.id } label: { listRow(survey) }
                        .buttonStyle(.plain)
                }
            }
        }
    }

    private func listRow(_ survey: SurveyDetail) -> some View {
        HStack(spacing: 12) {
            FontAwesomeIcon(systemName: "list.clipboard.fill")
                .foregroundStyle(accent)
                .frame(width: 40, height: 40)
                .background(accent.opacity(0.12))
                .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
            VStack(alignment: .leading, spacing: 2) {
                Text(survey.title).font(.subheadline.weight(.semibold)).foregroundStyle(AppDesign.ink)
                Text("\(survey.questions.count)問" + (survey.answered ? " · 回答済み" : ""))
                    .font(.caption).foregroundStyle(AppDesign.inkSecondary)
            }
            Spacer(minLength: 0)
            if !survey.answered && survey.rewardPoints > 0 {
                rewardChip("\(survey.rewardPoints)pt")
            }
            FontAwesomeIcon(systemName: "chevron.right").font(.caption).foregroundStyle(AppDesign.inkSecondary)
        }
        .padding(14)
        .background(AppDesign.surface)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
    }

    private func form(_ survey: SurveyDetail) -> some View {
        VStack(alignment: .leading, spacing: 12) {
            header(survey)
            ForEach(Array(survey.questions.enumerated()), id: \.offset) { index, question in
                questionCard(question, index: index)
            }
            if let message = viewModel.errorMessage {
                Text(message).font(.caption).foregroundStyle(AppDesign.danger)
            }
        }
    }

    private func header(_ survey: SurveyDetail) -> some View {
        VStack(alignment: .leading, spacing: 8) {
            Text(survey.title).font(.title3.weight(.bold)).foregroundStyle(Color.white)
            if !survey.description.isEmpty {
                Text(survey.description).font(.subheadline).foregroundStyle(Color.white.opacity(0.85))
            }
            HStack(spacing: 8) {
                Text("全\(survey.questions.count)問").font(.caption).foregroundStyle(Color.white.opacity(0.85))
                if survey.rewardPoints > 0 {
                    rewardChip("回答で\(survey.rewardPoints)ポイント")
                }
            }
        }
        .padding(18)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(accent)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
    }

    private func questionCard(_ question: SurveyQuestion, index: Int) -> some View {
        let error = viewModel.fieldErrors[index]
        return VStack(alignment: .leading, spacing: 10) {
            HStack(alignment: .firstTextBaseline, spacing: 8) {
                Text("Q\(index + 1)")
                    .font(.caption.weight(.bold))
                    .foregroundStyle(accent)
                    .padding(.horizontal, 7)
                    .padding(.vertical, 2)
                    .background(accent.opacity(0.12))
                    .clipShape(RoundedRectangle(cornerRadius: 6))
                Text(question.label).font(.subheadline.weight(.semibold)).foregroundStyle(AppDesign.ink)
                Spacer(minLength: 0)
                if question.required {
                    Text("必須")
                        .font(.caption2.weight(.bold))
                        .foregroundStyle(AppDesign.danger)
                        .padding(.horizontal, 6)
                        .padding(.vertical, 2)
                        .background(AppDesign.danger.opacity(0.1))
                        .clipShape(RoundedRectangle(cornerRadius: 6))
                }
            }
            if !question.help.isEmpty {
                Text(question.help).font(.caption).foregroundStyle(AppDesign.inkSecondary)
            }
            field(question, index: index)
            if let error {
                Text(error).font(.caption).foregroundStyle(AppDesign.danger)
            }
        }
        .padding(16)
        .frame(maxWidth: .infinity, alignment: .leading)
        .background(AppDesign.surface)
        .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusL))
        .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusL).stroke(error == nil ? Color.clear : AppDesign.danger, lineWidth: 1))
    }

    @ViewBuilder
    private func field(_ question: SurveyQuestion, index: Int) -> some View {
        switch question.kind {
        case .nps:
            npsScale(index: index)
        case .rating:
            stars(index: index)
        case .choice, .multiChoice:
            choices(question, index: index)
        case .date:
            Toggle("日付を入力する", isOn: Binding(
                get: { dates[index] != nil },
                set: { dates[index] = $0 ? Date() : nil; updateDate(index) }
            ))
            if dates[index] != nil {
                DatePicker(question.label, selection: Binding(
                    get: { dates[index] ?? Date() },
                    set: { dates[index] = $0; updateDate(index) }
                ), displayedComponents: [.date])
                .labelsHidden()
            }
        case .number, .text:
            let text = Binding(
                get: { if case .text(let value) = viewModel.answers[index] { return value } else { return "" } },
                set: { viewModel.answers[index] = .text($0) }
            )
            if question.kind == .number {
                inputBox(TextField("数字を入力", text: text).keyboardType(.decimalPad), label: question.label)
            } else {
                inputBox(TextEditor(text: text).frame(minHeight: 96).scrollContentBackground(.hidden), label: question.label)
            }
        }
    }

    private func npsScale(index: Int) -> some View {
        VStack(spacing: 6) {
            HStack(spacing: 4) {
                ForEach(0...10, id: \.self) { score in
                    let selected = viewModel.answers[index] == .integer(score)
                    Button { viewModel.answers[index] = .integer(score) } label: {
                        Text("\(score)")
                            .font(.subheadline.weight(.semibold))
                            .frame(maxWidth: .infinity, minHeight: 44)
                            .foregroundStyle(selected ? Color.white : AppDesign.ink)
                            .background(selected ? npsColor(score) : AppDesign.surfaceMuted)
                            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusS))
                    }
                    .buttonStyle(.plain)
                    .accessibilityLabel(Text("\(score)点"))
                    .accessibilityAddTraits(selected ? .isSelected : [])
                }
            }
            HStack {
                Text("すすめない")
                Spacer()
                Text("ぜひすすめる")
            }
            .font(.caption)
            .foregroundStyle(AppDesign.inkSecondary)
        }
    }

    private func stars(index: Int) -> some View {
        let current: Int = {
            if case .integer(let value) = viewModel.answers[index] { return value }
            return 0
        }()
        return HStack(spacing: 4) {
            ForEach(1...5, id: \.self) { score in
                Button { viewModel.answers[index] = .integer(score) } label: {
                    FontAwesomeIcon(systemName: "star.fill")
                        .font(.title2)
                        .foregroundStyle(score <= current ? Color(red: 0.96, green: 0.65, blue: 0.14) : AppDesign.hairline)
                        .frame(width: 44, height: 44)
                }
                .buttonStyle(.plain)
                .accessibilityLabel(Text("\(score)つ星（\(Self.ratingWords[score - 1])）"))
                .accessibilityAddTraits(score == current ? .isSelected : [])
            }
            Text(current > 0 ? Self.ratingWords[current - 1] : "タップして評価")
                .font(.caption)
                .foregroundStyle(AppDesign.inkSecondary)
                .padding(.leading, 6)
        }
    }

    @ViewBuilder
    private func choices(_ question: SurveyQuestion, index: Int) -> some View {
        let multiple = question.kind == .multiChoice
        let keys = question.options + (question.allowOther ? [SurveyOtherAnswer.key] : [])
        VStack(alignment: .leading, spacing: 8) {
            if multiple && question.help.isEmpty {
                Text("当てはまるものをすべて選んでください").font(.caption).foregroundStyle(AppDesign.inkSecondary)
            }
            ForEach(keys, id: \.self) { key in
                let selected = viewModel.isSelected(key, at: index)
                optionRow(key == SurveyOtherAnswer.key ? SurveyOtherAnswer.label : key, selected: selected, multiple: multiple) {
                    if multiple {
                        viewModel.toggle(key, at: index)
                    } else {
                        viewModel.answers[index] = .text(key)
                    }
                }
            }
            if viewModel.isSelected(SurveyOtherAnswer.key, at: index) {
                inputBox(TextField("内容を入力してください", text: Binding(
                    get: { viewModel.otherTexts[index] ?? "" },
                    set: { viewModel.otherTexts[index] = String($0.prefix(SurveyOtherAnswer.maxLength)) }
                )), label: "\(question.label)（その他の内容）")
            }
        }
    }

    private func optionRow(_ option: String, selected: Bool, multiple: Bool, action: @escaping () -> Void) -> some View {
        Button(action: action) {
            HStack(spacing: 12) {
                FontAwesomeIcon(systemName: selected ? (multiple ? "square.checkmark" : "circle.circle.fill") : (multiple ? "square" : "circle"))
                    .foregroundStyle(selected ? accent : AppDesign.hairline)
                    .frame(width: 24, height: 24)
                Text(option)
                    .font(selected ? .subheadline.weight(.semibold) : .subheadline)
                    .foregroundStyle(AppDesign.ink)
                Spacer(minLength: 0)
            }
            .padding(.horizontal, 14)
            .frame(minHeight: 48)
            .background(selected ? accent.opacity(0.07) : AppDesign.surface)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
            .overlay(RoundedRectangle(cornerRadius: AppDesign.radiusM).stroke(selected ? accent : AppDesign.hairline, lineWidth: 1))
        }
        .buttonStyle(.plain)
        .accessibilityAddTraits(selected ? .isSelected : [])
    }

    private func inputBox(_ input: some View, label: String) -> some View {
        input
            .padding(12)
            .background(AppDesign.surfaceMuted)
            .clipShape(RoundedRectangle(cornerRadius: AppDesign.radiusM))
            .accessibilityLabel(Text(label))
    }

    private func progress(answered: Int, total: Int) -> some View {
        GeometryReader { proxy in
            ZStack(alignment: .leading) {
                Capsule().fill(AppDesign.hairline)
                Capsule().fill(AppDesign.coral)
                    .frame(width: total > 0 ? proxy.size.width * CGFloat(answered) / CGFloat(total) : 0)
            }
        }
        .frame(height: 6)
        .padding(.horizontal, 16)
        .padding(.bottom, 4)
        .animation(.easeOut(duration: 0.25), value: answered)
        .accessibilityLabel(Text("回答の進み具合 \(answered) / \(total)問"))
    }

    private var submitBar: some View {
        VStack(spacing: 0) {
            Divider()
            AppPrimaryButton(title: "回答を送信する", isLoading: viewModel.isSubmitting) {
                Task { await viewModel.submit() }
            }
            .padding(.horizontal, 16)
            .padding(.vertical, 12)
        }
        .background(AppDesign.surface)
    }

    private func rewardChip(_ text: String) -> some View {
        HStack(spacing: 4) {
            FontAwesomeIcon(systemName: "gift.fill").font(.caption2)
            Text(text).font(.caption.weight(.bold))
        }
        .foregroundStyle(Color(red: 0.71, green: 0.33, blue: 0.04))
        .padding(.horizontal, 10)
        .padding(.vertical, 3)
        .background(Color(red: 1.0, green: 0.95, blue: 0.9))
        .clipShape(Capsule())
    }

    private func npsColor(_ score: Int) -> Color {
        score >= 9 ? AppDesign.success : score >= 7 ? Self.passive : AppDesign.danger
    }

    private func finished(icon: String, title: String, text: String) -> some View {
        VStack(spacing: 14) {
            AppEmptyState(title: title, subtitle: text, systemImage: icon)
            AppSecondaryButton(title: "閉じる", action: onClose)
        }
    }

    private func updateDate(_ index: Int) {
        guard let date = dates[index] else {
            viewModel.answers[index] = nil
            return
        }
        let parts = Calendar.current.dateComponents([.year, .month, .day], from: date)
        viewModel.answers[index] = .text(String(format: "%04d-%02d-%02d", parts.year ?? 0, parts.month ?? 0, parts.day ?? 0))
    }
}
